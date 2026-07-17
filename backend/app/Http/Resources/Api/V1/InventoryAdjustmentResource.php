<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Inventory\Models\InventoryAdjustment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property InventoryAdjustment $resource
 */
class InventoryAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $adjustment = $this->resource;

        return [
            'id' => (string) $adjustment->id,
            'branchId' => (string) $adjustment->branch_id,
            'adjustmentNumber' => $adjustment->adjustment_number,
            'status' => $adjustment->status,
            'reasonCode' => $adjustment->reason_code,
            'reasonNote' => $adjustment->reason_note,
            'effectiveAt' => $adjustment->effective_at?->toIso8601String(),
            'approvedByUserId' => $adjustment->approved_by_user_id ? (string) $adjustment->approved_by_user_id : null,
            'approvedAt' => optional($adjustment->approved_at)->toIso8601String(),
            'postedAt' => optional($adjustment->posted_at)->toIso8601String(),
            'reversalAdjustmentId' => $adjustment->reversal_adjustment_id ? (string) $adjustment->reversal_adjustment_id : null,
            'lineCount' => $adjustment->lines_count ?? ($adjustment->relationLoaded('lines') ? $adjustment->lines->count() : null),
            'lines' => $adjustment->relationLoaded('lines') ? $adjustment->lines->map(fn ($line) => [
                'id' => (string) $line->id,
                'lineNumber' => $line->line_number,
                'productId' => (string) $line->product_id,
                'productSku' => $line->product_sku_snapshot,
                'productName' => $line->product_name_snapshot,
                'beforeQuantity' => (string) $line->before_quantity,
                'quantityDelta' => (string) $line->quantity_delta,
                'afterQuantity' => (string) $line->after_quantity,
                'unitCost' => $line->unit_cost !== null ? (string) $line->unit_cost : null,
                'notes' => $line->notes,
            ])->values() : [],
            'version' => $adjustment->row_version,
        ];
    }
}
