<?php

namespace App\Domains\Planning\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Planning\Models\ForecastRunItem;
use App\Domains\Planning\Models\ReorderPolicy;
use Illuminate\Support\Facades\DB;

/**
 * Calculates the reorder point as expected demand during lead time plus
 * safety stock (CLAUDE.md section 52). The reorder point is always
 * derived and recalculated here — it is never directly editable through
 * the ReorderPolicy update endpoint.
 *
 * Lead time convention: this system uses calendar days everywhere, chosen
 * once here rather than per calculation, per CLAUDE.md's requirement to
 * fix the calendar/business-day convention system-wide.
 */
class RopService
{
    private const DEFAULT_DEMAND_LOOKBACK_DAYS = 90;

    public function __construct(private readonly SmaForecastService $forecastService)
    {
    }

    public function recalculate(ReorderPolicy $policy, ?int $forecastRunId, User $actor): ReorderPolicy
    {
        return DB::transaction(function () use ($policy, $forecastRunId, $actor) {
            $locked = ReorderPolicy::query()->lockForUpdate()->findOrFail($policy->id);

            [$dailyDemand, ] = $this->resolveDailyDemand($locked, $forecastRunId);
            $leadTimeDays = $this->resolveLeadTimeDays($locked);

            if ($leadTimeDays === null) {
                throw new PlanningException('MISSING_DEMAND_OR_LEAD_TIME', 422, 'No lead time is available for this product. Set a lead time override or a product default lead time.');
            }

            $leadTimeDemand = bcmul($dailyDemand, (string) $leadTimeDays, 4);
            $reorderPoint = bcadd($leadTimeDemand, $locked->safety_stock_quantity, 4);

            $locked->reorder_point_quantity = $reorderPoint;
            $locked->rop_calculated_at = now();
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            return $locked;
        });
    }

    /**
     * @return array{0:string, 1:?int} [dailyDemand, forecastRunId used]
     */
    private function resolveDailyDemand(ReorderPolicy $policy, ?int $forecastRunId): array
    {
        if ($forecastRunId !== null) {
            $item = ForecastRunItem::query()
                ->where('forecast_run_id', $forecastRunId)
                ->where('product_id', $policy->product_id)
                ->first();

            if (! $item || $item->forecast_quantity === null) {
                throw new PlanningException('MISSING_DEMAND_OR_LEAD_TIME', 422, 'The selected forecast run has no usable demand result for this product.');
            }

            $run = $item->forecastRun;
            $periodLengthDays = SmaForecastService::periodLengthDays($run->period_grain);

            return [bcdiv((string) $item->forecast_quantity, (string) $periodLengthDays, 4), $forecastRunId];
        }

        return [$this->forecastService->calculateAverageDailyDemand($policy->branch_id, $policy->product_id, self::DEFAULT_DEMAND_LOOKBACK_DAYS), null];
    }

    private function resolveLeadTimeDays(ReorderPolicy $policy): ?string
    {
        if ($policy->lead_time_days_override !== null) {
            return (string) $policy->lead_time_days_override;
        }

        $product = $policy->product;

        if ($product->default_lead_time_days !== null) {
            return (string) $product->default_lead_time_days;
        }

        return null;
    }
}
