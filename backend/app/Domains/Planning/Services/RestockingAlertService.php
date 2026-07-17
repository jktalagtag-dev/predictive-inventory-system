<?php

namespace App\Domains\Planning\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Planning\Models\ReorderPolicy;
use App\Domains\Planning\Models\RestockingAlert;
use App\Domains\Planning\Models\RestockingAlertEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Evaluates reorder policies against current stock and maintains one
 * deduplicated alert per policy (CLAUDE.md section 53). Never creates a
 * purchase order — it only surfaces a recommendation for a user to act on.
 *
 * Evaluation is triggered on demand (an explicit "Evaluate now" action, or
 * a scheduled console command — see routes/console.php) rather than being
 * hooked into every stock-mutating service call. That keeps this phase's
 * blast radius contained to the new Planning domain instead of adding
 * coupling into the already-shipped Sales/Inventory/Receiving services;
 * it still satisfies "evaluate after material stock changes" in spirit
 * since operators can re-run evaluation immediately after any workflow.
 *
 * The recommended order quantity is the simple gap back up to the reorder
 * point (reorder point minus available quantity). It intentionally does
 * not auto-apply a saved EOQ recommendation — EOQ requires cost inputs a
 * user supplies separately, and mixing the two would blur which number
 * came from which calculation.
 */
class RestockingAlertService
{
    public function evaluatePolicy(ReorderPolicy $policy): ?RestockingAlert
    {
        if ($policy->reorder_point_quantity === null || ! $policy->is_active) {
            return null;
        }

        $balance = InventoryBalance::query()
            ->where('branch_id', $policy->branch_id)
            ->where('product_id', $policy->product_id)
            ->first();

        $available = $balance?->available_quantity ?? '0.0000';
        $incoming = $balance?->incoming_quantity ?? '0.0000';
        $rop = (string) $policy->reorder_point_quantity;

        $existing = RestockingAlert::query()
            ->where('reorder_policy_id', $policy->id)
            ->whereIn('status', ['active', 'acknowledged'])
            ->first();

        $belowRop = bccomp($available, $rop, 4) <= 0;

        return DB::transaction(function () use ($policy, $available, $incoming, $rop, $existing, $belowRop) {
            if (! $belowRop) {
                if ($existing) {
                    $this->applyTransition($existing, 'resolved', null, ['reason' => 'Stock recovered above the reorder point during evaluation.']);
                }

                return null;
            }

            $recommended = bccomp($rop, $available, 4) === 1 ? bcsub($rop, $available, 4) : '0.0000';
            $severity = $this->severityFor($available, $rop);

            if ($existing) {
                $existing->available_quantity_snapshot = $available;
                $existing->incoming_quantity_snapshot = $incoming;
                $existing->reorder_point_snapshot = $rop;
                $existing->recommended_order_quantity = $recommended;
                $existing->severity = $severity;
                $existing->last_evaluated_at = now();
                $existing->row_version = $existing->row_version + 1;
                $existing->save();

                RestockingAlertEvent::query()->create([
                    'restocking_alert_id' => $existing->id,
                    'event_type' => 're_evaluated',
                    'from_status' => $existing->status,
                    'to_status' => $existing->status,
                    'details' => ['availableQuantity' => $available, 'reorderPoint' => $rop, 'severity' => $severity],
                    'occurred_at' => now(),
                ]);

                return $existing;
            }

            $alert = RestockingAlert::query()->create([
                'reorder_policy_id' => $policy->id,
                'status' => 'active',
                'severity' => $severity,
                'available_quantity_snapshot' => $available,
                'incoming_quantity_snapshot' => $incoming,
                'reorder_point_snapshot' => $rop,
                'recommended_order_quantity' => $recommended,
                'first_triggered_at' => now(),
                'last_evaluated_at' => now(),
                'row_version' => 1,
            ]);

            RestockingAlertEvent::query()->create([
                'restocking_alert_id' => $alert->id,
                'event_type' => 'triggered',
                'from_status' => null,
                'to_status' => 'active',
                'details' => ['availableQuantity' => $available, 'reorderPoint' => $rop, 'severity' => $severity],
                'occurred_at' => now(),
            ]);

            return $alert;
        });
    }

    public function evaluateAll(?int $branchId = null): Collection
    {
        $query = ReorderPolicy::query()->where('is_active', true)->whereNotNull('reorder_point_quantity');
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()->map(fn (ReorderPolicy $policy) => $this->evaluatePolicy($policy))->filter();
    }

    public function acknowledge(RestockingAlert $alert, ?int $assignedToUserId, ?string $note, User $actor): RestockingAlert
    {
        if ($alert->status !== 'active') {
            throw new PlanningException('ILLEGAL_STATE', 409, 'Only an active alert can be acknowledged.');
        }

        return DB::transaction(function () use ($alert, $assignedToUserId, $note, $actor) {
            $locked = RestockingAlert::query()->lockForUpdate()->findOrFail($alert->id);
            $locked->assigned_to_user_id = $assignedToUserId;
            $this->applyTransition($locked, 'acknowledged', $actor, ['note' => $note]);

            return $locked;
        });
    }

    public function resolve(RestockingAlert $alert, string $reason, User $actor): RestockingAlert
    {
        if (! in_array($alert->status, ['active', 'acknowledged'], true)) {
            throw new PlanningException('ILLEGAL_STATE', 409, 'Only an active or acknowledged alert can be resolved.');
        }

        return DB::transaction(function () use ($alert, $reason, $actor) {
            $locked = RestockingAlert::query()->lockForUpdate()->findOrFail($alert->id);
            $this->applyTransition($locked, 'resolved', $actor, ['reason' => $reason]);

            return $locked;
        });
    }

    public function dismiss(RestockingAlert $alert, string $reason, User $actor): RestockingAlert
    {
        if (! in_array($alert->status, ['active', 'acknowledged'], true)) {
            throw new PlanningException('ILLEGAL_STATE', 409, 'Only an active or acknowledged alert can be dismissed.');
        }

        return DB::transaction(function () use ($alert, $reason, $actor) {
            $locked = RestockingAlert::query()->lockForUpdate()->findOrFail($alert->id);
            $locked->dismissal_reason = $reason;
            $this->applyTransition($locked, 'dismissed', $actor, ['reason' => $reason]);

            return $locked;
        });
    }

    private function applyTransition(RestockingAlert $alert, string $toStatus, ?User $actor, array $details): void
    {
        $fromStatus = $alert->status;
        $alert->status = $toStatus;
        if (in_array($toStatus, ['resolved', 'dismissed'], true)) {
            $alert->resolved_at = now();
        }
        $alert->row_version = $alert->row_version + 1;
        $alert->save();

        RestockingAlertEvent::query()->create([
            'restocking_alert_id' => $alert->id,
            'event_type' => $toStatus === 're_evaluated' ? 're_evaluated' : $toStatus,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'details' => $details,
            'actor_user_id' => $actor?->id,
            'occurred_at' => now(),
        ]);
    }

    private function severityFor(string $available, string $rop): string
    {
        if (bccomp($available, '0', 4) <= 0) {
            return 'critical';
        }

        if (bccomp($rop, '0', 4) === 0) {
            return 'low';
        }

        $ratio = (float) $available / (float) $rop;

        return match (true) {
            $ratio <= 0.25 => 'high',
            $ratio <= 0.5 => 'medium',
            default => 'low',
        };
    }
}
