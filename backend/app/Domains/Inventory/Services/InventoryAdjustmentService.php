<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Catalog\Models\Product;
use App\Domains\Governance\Services\AuditLogger;
use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\InventoryAdjustment;
use App\Domains\Inventory\Models\InventoryAdjustmentLine;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Inventory\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Owns the inventory adjustment state machine: draft -> pending_approval
 * -> posted -> reversed. Posting and reversal are the only actions that
 * mutate inventory_balances and append inventory_movements; both happen
 * inside one transaction with the document state change.
 *
 * Every state-changing method writes an audit entry inside the same
 * transaction as the fact it describes (CLAUDE.md section 55).
 */
class InventoryAdjustmentService
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    /**
     * @param array{branch_id:int, reason_code:string, reason_note:?string, effective_at:\DateTimeInterface, lines:array} $data
     */
    public function createDraft(array $data, User $actor, string $correlationId): InventoryAdjustment
    {
        return DB::transaction(function () use ($data, $actor, $correlationId) {
            $adjustment = InventoryAdjustment::query()->create([
                'branch_id' => $data['branch_id'],
                'adjustment_number' => 'ADJ-'.strtoupper(Str::random(10)),
                'status' => 'pending_approval',
                'reason_code' => $data['reason_code'],
                'reason_note' => $data['reason_note'] ?? null,
                'effective_at' => $data['effective_at'],
                'row_version' => 1,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            $this->replaceLines($adjustment, $data['lines'], $data['branch_id'], $actor);

            $adjustment->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'inventory_adjustment.created', 'inventory_adjustment', $adjustment->id, $adjustment->branch_id, $correlationId,
                null, ['status' => $adjustment->status, 'adjustmentNumber' => $adjustment->adjustment_number, 'reasonCode' => $adjustment->reason_code],
            );

            return $adjustment;
        });
    }

    /**
     * @param array{reason_code?:string, reason_note?:?string, effective_at?:\DateTimeInterface, lines?:array} $data
     */
    public function updateDraft(InventoryAdjustment $adjustment, array $data, User $actor, string $correlationId): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $data, $actor, $correlationId) {
            $locked = InventoryAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);

            if ($locked->status !== 'pending_approval' || $locked->approved_at !== null) {
                throw new InventoryAdjustmentException('ILLEGAL_STATE', 409, 'Only an unapproved draft adjustment can be updated.');
            }

            $before = ['reasonCode' => $locked->reason_code, 'reasonNote' => $locked->reason_note];

            $locked->fill(array_filter([
                'reason_code' => $data['reason_code'] ?? null,
                'reason_note' => array_key_exists('reason_note', $data) ? $data['reason_note'] : null,
                'effective_at' => $data['effective_at'] ?? null,
            ], fn ($value) => $value !== null));
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            if (array_key_exists('lines', $data)) {
                $this->replaceLines($locked, $data['lines'], $locked->branch_id, $actor);
            }

            $locked->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'inventory_adjustment.updated', 'inventory_adjustment', $locked->id, $locked->branch_id, $correlationId,
                $before, ['reasonCode' => $locked->reason_code, 'reasonNote' => $locked->reason_note],
            );

            return $locked;
        });
    }

    public function approve(InventoryAdjustment $adjustment, User $actor, string $correlationId): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor, $correlationId) {
            $locked = InventoryAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);

            if ($locked->status !== 'pending_approval') {
                throw new InventoryAdjustmentException('ILLEGAL_STATE', 409, 'Only a pending adjustment can be approved.');
            }

            if ($locked->approved_at !== null) {
                throw new InventoryAdjustmentException('ILLEGAL_STATE', 409, 'This adjustment has already been approved.');
            }

            if ($locked->created_by_user_id === $actor->id) {
                throw new InventoryAdjustmentException('SELF_APPROVAL_DENIED', 403, 'You cannot approve an adjustment you created yourself.');
            }

            $locked->approved_by_user_id = $actor->id;
            $locked->approved_at = now();
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            $locked->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'inventory_adjustment.approved', 'inventory_adjustment', $locked->id, $locked->branch_id, $correlationId,
                ['approvedByUserId' => null], ['approvedByUserId' => $locked->approved_by_user_id],
            );

            return $locked;
        });
    }

    public function post(InventoryAdjustment $adjustment, User $actor, string $correlationId): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor, $correlationId) {
            $locked = InventoryAdjustment::query()->lockForUpdate()->with('lines')->findOrFail($adjustment->id);

            if ($locked->status !== 'pending_approval' || $locked->approved_at === null) {
                throw new InventoryAdjustmentException('ILLEGAL_STATE', 409, 'Only an approved, unposted adjustment can be posted.');
            }

            foreach ($locked->lines as $line) {
                $this->applyLineToBalance($locked, $line, $line->quantity_delta, 'adjustment', null, $actor, $correlationId);
            }

            $locked->status = 'posted';
            $locked->posted_at = now();
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            $locked->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'inventory_adjustment.posted', 'inventory_adjustment', $locked->id, $locked->branch_id, $correlationId,
                ['status' => 'pending_approval'], ['status' => 'posted', 'lineCount' => $locked->lines->count()],
            );

            return $locked;
        });
    }

    public function reverse(InventoryAdjustment $adjustment, string $reason, User $actor, string $correlationId): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $reason, $actor, $correlationId) {
            $locked = InventoryAdjustment::query()->lockForUpdate()->with('lines')->findOrFail($adjustment->id);

            if ($locked->status !== 'posted') {
                throw new InventoryAdjustmentException('ILLEGAL_STATE', 409, 'Only a posted adjustment can be reversed.');
            }

            $compensating = InventoryAdjustment::query()->create([
                'branch_id' => $locked->branch_id,
                'adjustment_number' => 'ADJ-'.strtoupper(Str::random(10)),
                'status' => 'pending_approval',
                'reason_code' => 'reversal',
                'reason_note' => $reason,
                'effective_at' => now(),
                'approved_by_user_id' => $actor->id,
                'approved_at' => now(),
                'row_version' => 1,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            $lineNumber = 1;
            foreach ($locked->lines as $originalLine) {
                $originalMovement = InventoryMovement::query()
                    ->where('reference_type', 'inventory_adjustment_line')
                    ->where('reference_id', $originalLine->id)
                    ->first();

                $reversedDelta = (string) bcmul($originalLine->quantity_delta, '-1', 4);
                $balance = $this->lockOrCreateBalance($locked->branch_id, $originalLine->product_id);

                $compensatingLine = InventoryAdjustmentLine::query()->create([
                    'inventory_adjustment_id' => $compensating->id,
                    'line_number' => $lineNumber++,
                    'product_id' => $originalLine->product_id,
                    'product_sku_snapshot' => $originalLine->product_sku_snapshot,
                    'product_name_snapshot' => $originalLine->product_name_snapshot,
                    'before_quantity' => $balance->on_hand_quantity,
                    'quantity_delta' => $reversedDelta,
                    'after_quantity' => bcadd($balance->on_hand_quantity, $reversedDelta, 4),
                    'created_by_user_id' => $actor->id,
                ]);

                $this->applyLineToBalance(
                    $compensating,
                    $compensatingLine,
                    $reversedDelta,
                    'reversal',
                    $originalMovement?->id,
                    $actor,
                    $correlationId,
                );
            }

            $compensating->status = 'posted';
            $compensating->posted_at = now();
            $compensating->save();

            $locked->status = 'reversed';
            $locked->reversal_adjustment_id = $compensating->id;
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            $locked->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'inventory_adjustment.reversed', 'inventory_adjustment', $locked->id, $locked->branch_id, $correlationId,
                ['status' => 'posted'], ['status' => 'reversed', 'reason' => $reason, 'reversalAdjustmentId' => $compensating->id],
            );

            return $locked;
        });
    }

    /**
     * @param array<int, array{product_id:int, quantity_delta:string, unit_cost?:?string, notes?:?string}> $lines
     */
    private function replaceLines(InventoryAdjustment $adjustment, array $lines, int $branchId, User $actor): void
    {
        $adjustment->lines()->delete();

        $lineNumber = 1;
        foreach ($lines as $line) {
            $balance = $this->findOrProjectBalance($branchId, $line['product_id']);
            $before = $balance['on_hand_quantity'];
            $after = bcadd($before, $line['quantity_delta'], 4);

            if (bccomp($after, '0', 4) === -1) {
                throw new InventoryAdjustmentException(
                    'NEGATIVE_STOCK',
                    422,
                    "This adjustment would drive on-hand quantity negative for product {$balance['product_sku']}.",
                );
            }

            $product = Product::query()->findOrFail($line['product_id']);

            InventoryAdjustmentLine::query()->create([
                'inventory_adjustment_id' => $adjustment->id,
                'line_number' => $lineNumber++,
                'product_id' => $product->id,
                'product_sku_snapshot' => $product->sku,
                'product_name_snapshot' => $product->name,
                'before_quantity' => $before,
                'quantity_delta' => $line['quantity_delta'],
                'after_quantity' => $after,
                'unit_cost' => $line['unit_cost'] ?? null,
                'notes' => $line['notes'] ?? null,
                'created_by_user_id' => $actor->id,
            ]);
        }
    }

    /**
     * Reads the current balance without locking, for draft preview only.
     * Posting always re-reads under a row lock via lockOrCreateBalance().
     *
     * @return array{on_hand_quantity:string, product_sku:string}
     */
    private function findOrProjectBalance(int $branchId, int $productId): array
    {
        $balance = InventoryBalance::query()->where('branch_id', $branchId)->where('product_id', $productId)->first();
        $product = Product::query()->findOrFail($productId);

        return [
            'on_hand_quantity' => $balance->on_hand_quantity ?? '0.0000',
            'product_sku' => $product->sku,
        ];
    }

    private function lockOrCreateBalance(int $branchId, int $productId): InventoryBalance
    {
        $balance = InventoryBalance::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($balance) {
            return $balance;
        }

        return InventoryBalance::query()->create([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'on_hand_quantity' => 0,
            'reserved_quantity' => 0,
            'available_quantity' => 0,
            'incoming_quantity' => 0,
            'row_version' => 1,
        ]);
    }

    private function applyLineToBalance(
        InventoryAdjustment $adjustment,
        InventoryAdjustmentLine $line,
        string $quantityDelta,
        string $movementType,
        ?int $reversesMovementId,
        User $actor,
        string $correlationId,
    ): void {
        $balance = $this->lockOrCreateBalance($adjustment->branch_id, $line->product_id);

        $newOnHand = bcadd($balance->on_hand_quantity, $quantityDelta, 4);

        if (bccomp($newOnHand, '0', 4) === -1) {
            throw new InventoryAdjustmentException(
                'NEGATIVE_STOCK',
                422,
                "Posting this adjustment would drive on-hand quantity negative for product {$line->product_sku_snapshot}.",
            );
        }

        $balance->on_hand_quantity = $newOnHand;
        $balance->available_quantity = bcsub($newOnHand, $balance->reserved_quantity, 4);
        $balance->last_movement_at = now();
        $balance->row_version = $balance->row_version + 1;
        $balance->save();

        InventoryMovement::query()->create([
            'branch_id' => $adjustment->branch_id,
            'product_id' => $line->product_id,
            'movement_type' => $movementType,
            'quantity_delta' => $quantityDelta,
            'on_hand_after_quantity' => $newOnHand,
            'reference_type' => 'inventory_adjustment_line',
            'reference_id' => $line->id,
            'reverses_movement_id' => $reversesMovementId,
            'effective_at' => $adjustment->effective_at,
            'posted_at' => now(),
            'actor_user_id' => $actor->id,
            'correlation_id' => $correlationId,
        ]);
    }
}
