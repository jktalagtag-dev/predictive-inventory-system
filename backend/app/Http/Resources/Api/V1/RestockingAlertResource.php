<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Planning\Models\RestockingAlert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property RestockingAlert $resource
 */
class RestockingAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $alert = $this->resource;
        $policy = $alert->relationLoaded('reorderPolicy') ? $alert->reorderPolicy : null;

        return [
            'id' => (string) $alert->id,
            'reorderPolicyId' => (string) $alert->reorder_policy_id,
            'branchId' => $policy ? (string) $policy->branch_id : null,
            'productId' => $policy ? (string) $policy->product_id : null,
            'productSku' => $policy && $policy->relationLoaded('product') && $policy->product ? $policy->product->sku : null,
            'productName' => $policy && $policy->relationLoaded('product') && $policy->product ? $policy->product->name : null,
            'status' => $alert->status,
            'severity' => $alert->severity,
            'availableQuantitySnapshot' => (string) $alert->available_quantity_snapshot,
            'incomingQuantitySnapshot' => (string) $alert->incoming_quantity_snapshot,
            'reorderPointSnapshot' => (string) $alert->reorder_point_snapshot,
            'recommendedOrderQuantity' => $alert->recommended_order_quantity !== null ? (string) $alert->recommended_order_quantity : null,
            'firstTriggeredAt' => optional($alert->first_triggered_at)->toIso8601String(),
            'lastEvaluatedAt' => optional($alert->last_evaluated_at)->toIso8601String(),
            'resolvedAt' => optional($alert->resolved_at)->toIso8601String(),
            'dismissalReason' => $alert->dismissal_reason,
            'assignedToUserId' => $alert->assigned_to_user_id ? (string) $alert->assigned_to_user_id : null,
            'events' => $alert->relationLoaded('events') ? $alert->events->sortByDesc('occurred_at')->values()->map(fn ($event) => [
                'id' => (string) $event->id,
                'eventType' => $event->event_type,
                'fromStatus' => $event->from_status,
                'toStatus' => $event->to_status,
                'details' => $event->details,
                'occurredAt' => optional($event->occurred_at)->toIso8601String(),
            ]) : [],
            'version' => $alert->row_version,
        ];
    }
}
