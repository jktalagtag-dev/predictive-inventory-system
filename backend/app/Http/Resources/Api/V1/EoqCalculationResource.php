<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Planning\Models\EoqCalculation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property EoqCalculation $resource
 */
class EoqCalculationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $eoq = $this->resource;

        return [
            'id' => (string) $eoq->id,
            'reorderPolicyId' => (string) $eoq->reorder_policy_id,
            'annualDemandQuantity' => (string) $eoq->annual_demand_quantity,
            'orderingCost' => (string) $eoq->ordering_cost,
            'annualHoldingCostPerUnit' => (string) $eoq->annual_holding_cost_per_unit,
            'rawEoqQuantity' => $eoq->raw_eoq_quantity !== null ? (string) $eoq->raw_eoq_quantity : null,
            'recommendedOrderQuantity' => $eoq->recommended_order_quantity !== null ? (string) $eoq->recommended_order_quantity : null,
            'currencyCode' => $eoq->currency_code,
            'formulaVersion' => $eoq->formula_version,
            'status' => $eoq->status,
            'invalidReason' => $eoq->invalid_reason,
            'calculatedAt' => optional($eoq->calculated_at)->toIso8601String(),
        ];
    }
}
