<?php

namespace App\Domains\Planning\Services;

use App\Domains\Catalog\Models\Product;
use App\Domains\Identity\Models\User;
use App\Domains\Planning\Models\ForecastRun;
use App\Domains\Planning\Models\ForecastRunItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Calculates a Simple Moving Average forecast over finalized net demand
 * (CLAUDE.md section 50). A forecast run is immutable once created: a
 * changed input always produces a new run rather than rewriting a prior
 * one, preserving historical forecasts for comparison and audit.
 *
 * Deviation from DATABASE_DESIGN.md: period boundaries are computed as
 * fixed-length windows counting forward from historyStartDate (1 day for
 * daily, 7 for weekly, 30 for monthly) rather than calendar-aligned weeks
 * or months. This avoids timezone- and month-length ambiguity while still
 * producing a continuous, deterministic period series, and the requested
 * date range must divide evenly into exactly windowPeriods periods so the
 * boundaries are always explicit and reproducible.
 *
 * Net demand policy: a period's demand is the sum of sale_lines.quantity
 * for completed sales sold in that period, minus the quantity on any
 * refund documents (sales.reverses_sale_id IS NOT NULL) sold in that same
 * period. Voided sales are excluded entirely since they never represent
 * realized demand. Refunds reduce the period in which the refund itself
 * occurred, not the original sale's period — the declared net-sales
 * policy required by CLAUDE.md section 50.
 */
class SmaForecastService
{
    public const MIN_WINDOW_PERIODS = 2;

    public const MAX_WINDOW_PERIODS = 24;

    private const PERIOD_LENGTH_DAYS = ['daily' => 1, 'weekly' => 7, 'monthly' => 30];

    public const MODEL_VERSION = 'sma-v1';

    public static function periodLengthDays(string $periodGrain): int
    {
        return self::PERIOD_LENGTH_DAYS[$periodGrain] ?? 1;
    }

    /**
     * @param array{branch_id:int, period_grain:string, window_periods:int, history_start_date:string, history_end_date:string, product_ids:?array} $data
     */
    public function createRun(array $data, User $actor): ForecastRun
    {
        $periodGrain = $data['period_grain'];
        $windowPeriods = (int) $data['window_periods'];

        if (! array_key_exists($periodGrain, self::PERIOD_LENGTH_DAYS)) {
            throw new PlanningException('INVALID_PERIOD_WINDOW', 422, 'Unsupported period grain.');
        }

        if ($windowPeriods < self::MIN_WINDOW_PERIODS || $windowPeriods > self::MAX_WINDOW_PERIODS) {
            throw new PlanningException(
                'INVALID_PERIOD_WINDOW', 422,
                'Window periods must be between '.self::MIN_WINDOW_PERIODS.' and '.self::MAX_WINDOW_PERIODS.'.',
            );
        }

        $periodLengthDays = self::PERIOD_LENGTH_DAYS[$periodGrain];
        $historyStart = CarbonImmutable::parse($data['history_start_date'])->startOfDay();
        $historyEnd = CarbonImmutable::parse($data['history_end_date'])->startOfDay();
        $today = CarbonImmutable::now()->startOfDay();

        if ($historyEnd->lt($historyStart)) {
            throw new PlanningException('INVALID_DATE_RANGE', 422, 'The history end date must not be before the start date.');
        }

        if ($historyEnd->gte($today)) {
            throw new PlanningException('INVALID_DATE_RANGE', 422, 'The history end date must fall before the current, still-incomplete period.');
        }

        // Carbon 3's diffInDays() returns a signed float by default (unlike
        // Carbon 2's implicit absolute integer difference), so this is
        // normalized to an int to stay correct regardless of argument
        // order and to compare cleanly against the strict-typed check below.
        $totalDays = (int) round(abs($historyStart->diffInDays($historyEnd))) + 1;
        if ($totalDays !== $windowPeriods * $periodLengthDays) {
            throw new PlanningException(
                'INVALID_DATE_RANGE', 422,
                "The history range must contain exactly {$windowPeriods} complete {$periodGrain} periods.",
            );
        }

        $periods = [];
        for ($i = 0; $i < $windowPeriods; $i++) {
            $periodStart = $historyStart->addDays($i * $periodLengthDays);
            $periods[] = ['start' => $periodStart, 'end' => $periodStart->addDays($periodLengthDays - 1)];
        }

        $query = Product::query()->where('is_active', true)->where('product_type', 'stock');
        if (! empty($data['product_ids'])) {
            $query->whereIn('id', $data['product_ids']);
        }
        $products = $query->get();

        return DB::transaction(function () use ($data, $actor, $periodGrain, $windowPeriods, $historyStart, $historyEnd, $periods, $products) {
            $run = ForecastRun::query()->create([
                'branch_id' => $data['branch_id'],
                'model_code' => 'sma',
                'model_version' => self::MODEL_VERSION,
                'period_grain' => $periodGrain,
                'window_periods' => $windowPeriods,
                'history_start_date' => $historyStart->toDateString(),
                'history_end_date' => $historyEnd->toDateString(),
                'data_cutoff_at' => now(),
                'status' => 'completed',
                'started_at' => now(),
                'completed_at' => now(),
                'parameters_snapshot' => [
                    'branchId' => $data['branch_id'],
                    'periodGrain' => $periodGrain,
                    'windowPeriods' => $windowPeriods,
                    'historyStartDate' => $historyStart->toDateString(),
                    'historyEndDate' => $historyEnd->toDateString(),
                ],
                'created_by_user_id' => $actor->id,
            ]);

            foreach ($products as $product) {
                $periodDemands = [];
                foreach ($periods as $period) {
                    $periodDemands[] = (string) $this->netDemandForPeriod($data['branch_id'], $product->id, $period['start'], $period['end']);
                }

                $demandTotal = array_reduce($periodDemands, fn ($carry, $value) => bcadd($carry, $value, 4), '0.0000');
                $hasDemand = bccomp($demandTotal, '0', 4) === 1;
                $coldStartStatus = $hasDemand ? 'sufficient_history' : 'insufficient_history';
                $forecastQuantity = $hasDemand ? bcdiv($demandTotal, (string) $windowPeriods, 4) : null;

                ForecastRunItem::query()->create([
                    'forecast_run_id' => $run->id,
                    'product_id' => $product->id,
                    'product_sku_snapshot' => $product->sku,
                    'product_name_snapshot' => $product->name,
                    'history_period_count' => count($periods),
                    'demand_total' => $demandTotal,
                    'forecast_quantity' => $forecastQuantity,
                    'cold_start_status' => $coldStartStatus,
                    'input_snapshot' => [
                        'periods' => array_map(fn ($period, $demand) => [
                            'start' => $period['start']->toDateString(),
                            'end' => $period['end']->toDateString(),
                            'netDemand' => $demand,
                        ], $periods, $periodDemands),
                    ],
                ]);
            }

            return $run->refresh()->load('items');
        });
    }

    /**
     * Ad-hoc average daily net demand over a trailing lookback window, used
     * by RopService when no saved forecast run is supplied. This is still
     * derived from real transactional data (never a fabricated value) —
     * it simply skips persisting a ForecastRun snapshot for the lookup.
     */
    public function calculateAverageDailyDemand(int $branchId, int $productId, int $lookbackDays = 90): string
    {
        $end = CarbonImmutable::now()->startOfDay()->subDay();
        $start = $end->subDays($lookbackDays - 1);

        $total = $this->netDemandForPeriod($branchId, $productId, $start, $end);

        return bcdiv($total, (string) $lookbackDays, 4);
    }

    public function recordManualPlan(ForecastRunItem $item, string $manualQuantity, string $reason, string $expiresAt): ForecastRunItem
    {
        if (bccomp($manualQuantity, '0', 4) === -1) {
            throw new PlanningException('INVALID_MANUAL_QUANTITY', 422, 'The manual planning quantity must not be negative.');
        }

        if (CarbonImmutable::parse($expiresAt)->lte(now())) {
            throw new PlanningException('INVALID_MANUAL_EXPIRY', 422, 'The manual plan expiry must be in the future.');
        }

        $item->manual_quantity = $manualQuantity;
        $item->manual_reason = $reason;
        $item->manual_expires_at = $expiresAt;
        $item->cold_start_status = 'manual_override';
        $item->save();

        return $item;
    }

    private function netDemandForPeriod(int $branchId, int $productId, CarbonImmutable $start, CarbonImmutable $end): string
    {
        $windowStart = $start->toDateString().' 00:00:00';
        $windowEnd = $end->addDay()->toDateString().' 00:00:00';

        $sold = (string) DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sales.branch_id', $branchId)
            ->where('sales.status', 'completed')
            ->where('sale_lines.product_id', $productId)
            ->where('sales.sold_at', '>=', $windowStart)
            ->where('sales.sold_at', '<', $windowEnd)
            ->sum('sale_lines.quantity');

        $refunded = (string) DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sales.branch_id', $branchId)
            ->whereNotNull('sales.reverses_sale_id')
            ->where('sale_lines.product_id', $productId)
            ->where('sales.sold_at', '>=', $windowStart)
            ->where('sales.sold_at', '<', $windowEnd)
            ->sum('sale_lines.quantity');

        return bcsub($sold, $refunded, 4);
    }
}
