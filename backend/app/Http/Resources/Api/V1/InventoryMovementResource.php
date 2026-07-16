<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Inventory\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property InventoryMovement $resource
 */
class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $movement = $this->resource;

        return [
            'id' => (string) $movement->id,
            'branchId' => (string) $movement->branch_id,
            'product' => $movement->relationLoaded('product') && $movement->product ? [
                'id' => (string) $movement->product->id,
                'sku' => $movement->product->sku,
                'name' => $movement->product->name,
            ] : null,
            'movementType' => $movement->movement_type,
            'quantityDelta' => (string) $movement->quantity_delta,
            'onHandAfterQuantity' => $movement->on_hand_after_quantity !== null ? (string) $movement->on_hand_after_quantity : null,
            'referenceType' => $movement->reference_type,
            'referenceId' => (string) $movement->reference_id,
            'reversesMovementId' => $movement->reverses_movement_id ? (string) $movement->reverses_movement_id : null,
            'effectiveAt' => $movement->effective_at?->toIso8601String(),
            'postedAt' => $movement->posted_at?->toIso8601String(),
            'actor' => $movement->relationLoaded('actor') && $movement->actor ? [
                'id' => (string) $movement->actor->id,
                'displayName' => $movement->actor->display_name,
            ] : null,
            'correlationId' => $movement->correlation_id,
        ];
    }
}
