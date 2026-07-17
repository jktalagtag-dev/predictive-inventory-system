<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Planning\Models\ReorderPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ReorderPolicy $resource
 */
class ReorderPolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $policy = $this->resource;

        return [
            'id' => (string) $policy->id,
            'branchId' => (string) $policy->branch_id,
            'productId' => (string) $policy->product_id,
            'productSku' => $policy->relationLoaded('product') && $policy->product ? $policy->product->sku : null,
            'productName' => $policy->relationLoaded('product') && $policy->product ? $policy->product->name : null,
            'preferredSupplierId' => $policy->preferred_supplier_id ? (string) $policy->preferred_supplier_id : null,
            'preferredSupplierName' => $policy->relationLoaded('preferredSupplier') && $policy->preferredSupplier ? $policy->preferredSupplier->legal_name : null,
            'safetyStockQuantity' => (string) $policy->safety_stock_quantity,
            'safetyStockBasis' => $policy->safety_stock_basis,
            'leadTimeDaysOverride' => $policy->lead_time_days_override !== null ? (string) $policy->lead_time_days_override : null,
            'leadTimeBasis' => $policy->lead_time_basis,
            'reorderPointQuantity' => $policy->reorder_point_quantity !== null ? (string) $policy->reorder_point_quantity : null,
            'ropCalculatedAt' => optional($policy->rop_calculated_at)->toIso8601String(),
            'isActive' => (bool) $policy->is_active,
            'version' => $policy->row_version,
        ];
    }
}
