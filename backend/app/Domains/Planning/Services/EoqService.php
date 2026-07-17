<?php

namespace App\Domains\Planning\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Planning\Models\EoqCalculation;
use App\Domains\Planning\Models\ReorderPolicy;

/**
 * Computes the classic Economic Order Quantity formula and persists an
 * immutable snapshot (CLAUDE.md section 51). EOQ is always a recommendation
 * for a human to review, never a mandate — this service never creates a
 * purchase order.
 *
 * Formula: EOQ = sqrt(2 x annual demand x ordering cost / annual holding
 * cost per unit). Basic input validity (non-negative demand/ordering cost,
 * strictly positive holding cost) is enforced by the calling FormRequest,
 * so by the time this service runs the inputs are always well-formed and
 * the resulting snapshot always has status "valid"; the "invalid_input"
 * status exists in the schema for future automated/batch calculation
 * paths that may not pass through the same request-level guard.
 */
class EoqService
{
    public const FORMULA_VERSION = 'eoq-classic-v1';

    /**
     * @param array{annual_demand_quantity:string, ordering_cost:string, annual_holding_cost_per_unit:string, currency_code:string} $data
     */
    public function calculate(ReorderPolicy $policy, array $data, User $actor): EoqCalculation
    {
        $annualDemand = $data['annual_demand_quantity'];
        $orderingCost = $data['ordering_cost'];
        $holdingCost = $data['annual_holding_cost_per_unit'];

        $rawEoq = sqrt((2 * (float) $annualDemand * (float) $orderingCost) / (float) $holdingCost);
        $recommended = (string) ceil($rawEoq);

        return EoqCalculation::query()->create([
            'reorder_policy_id' => $policy->id,
            'annual_demand_quantity' => $annualDemand,
            'ordering_cost' => $orderingCost,
            'annual_holding_cost_per_unit' => $holdingCost,
            'raw_eoq_quantity' => (string) $rawEoq,
            'recommended_order_quantity' => $recommended,
            'currency_code' => $data['currency_code'],
            'formula_version' => self::FORMULA_VERSION,
            'input_snapshot' => [
                'annualDemandQuantity' => $annualDemand,
                'orderingCost' => $orderingCost,
                'annualHoldingCostPerUnit' => $holdingCost,
                'currencyCode' => $data['currency_code'],
                'roundingPolicy' => 'ceil_to_whole_unit',
            ],
            'status' => 'valid',
            'calculated_at' => now(),
            'created_by_user_id' => $actor->id,
        ]);
    }
}
