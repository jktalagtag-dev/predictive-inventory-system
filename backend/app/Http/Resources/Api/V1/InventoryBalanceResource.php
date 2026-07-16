<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Inventory\Models\InventoryBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property InventoryBalance $resource
 */
class InventoryBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $balance = $this->resource;

        return [
            'id' => (string) $balance->id,
            'branchId' => (string) $balance->branch_id,
            'product' => $balance->relationLoaded('product') && $balance->product ? [
                'id' => (string) $balance->product->id,
                'sku' => $balance->product->sku,
                'name' => $balance->product->name,
            ] : null,
            'onHandQuantity' => (string) $balance->on_hand_quantity,
            'reservedQuantity' => (string) $balance->reserved_quantity,
            'availableQuantity' => (string) $balance->available_quantity,
            'incomingQuantity' => (string) $balance->incoming_quantity,
            'lastMovementAt' => optional($balance->last_movement_at)->toIso8601String(),
            'version' => $balance->row_version,
        ];
    }
}
