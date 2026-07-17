<?php

namespace App\Domains\Dashboard\Services;

use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Planning\Models\ForecastRun;
use App\Domains\Planning\Models\RestockingAlert;
use App\Domains\Procurement\Models\PurchaseOrder;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sync\Models\SyncOperation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates the operational dashboard from already-authoritative domain
 * tables — it never recomputes a business rule that a domain service
 * already owns (CLAUDE.md section 8). Every figure here is a read-only
 * projection: low-stock counts come from RestockingAlert (Planning
 * domain's own severity classification), never a re-derived threshold.
 */
class DashboardService
{
    private const MAX_TREND_DAYS = 90;

    /**
     * @return array<string, mixed>
     */
    public function build(int $branchId, CarbonImmutable $from, CarbonImmutable $to, string $timezone): array
    {
        if ($from->greaterThan($to)) {
            throw new DashboardException('INVALID_DATE_RANGE', 422, 'The from date must not be after the to date.');
        }

        if ($from->diffInDays($to) > self::MAX_TREND_DAYS) {
            throw new DashboardException('INVALID_DATE_RANGE', 422, 'The date range cannot exceed '.self::MAX_TREND_DAYS.' days.');
        }

        return [
            'kpis' => $this->buildKpis($branchId, $timezone),
            'lowStock' => $this->buildLowStock($branchId),
            'pendingPurchaseOrders' => $this->buildPendingPurchaseOrders($branchId),
            'recentSales' => $this->buildRecentSales($branchId),
            'salesTrend' => $this->buildSalesTrend($branchId, $from, $to),
            'forecastSummary' => $this->buildForecastSummary($branchId),
            'syncHealth' => $this->buildSyncHealth($branchId),
        ];
    }

    /**
     * @return array<string, array{value: string, label: string, detail: string}>
     */
    private function buildKpis(int $branchId, string $timezone): array
    {
        $balances = InventoryBalance::query()->where('branch_id', $branchId)->get(['on_hand_quantity']);
        $onHandTotal = '0.0000';
        foreach ($balances as $balance) {
            $onHandTotal = bcadd($onHandTotal, $balance->on_hand_quantity, 4);
        }

        $todayStart = CarbonImmutable::now($timezone)->startOfDay();
        $todayEnd = CarbonImmutable::now($timezone)->endOfDay();

        $todaySales = Sale::query()
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$todayStart, $todayEnd])
            ->get(['total_amount']);

        $salesTotal = '0.0000';
        foreach ($todaySales as $sale) {
            $salesTotal = bcadd($salesTotal, $sale->total_amount, 4);
        }

        $activeAlerts = RestockingAlert::query()
            ->whereHas('reorderPolicy', fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', 'active')
            ->get(['severity']);

        $lowCount = $activeAlerts->whereIn('severity', ['low', 'medium', 'high'])->count();
        $criticalCount = $activeAlerts->where('severity', 'critical')->count();

        return [
            'inventoryOnHand' => [
                'value' => (string) $onHandTotal,
                'label' => 'Inventory on hand',
                'detail' => $balances->count().' stocked product'.($balances->count() === 1 ? '' : 's'),
            ],
            'salesToday' => [
                'value' => (string) $salesTotal,
                'label' => 'Sales today',
                'detail' => $todaySales->count().' completed transaction'.($todaySales->count() === 1 ? '' : 's'),
            ],
            'lowStockCount' => [
                'value' => (string) $lowCount,
                'label' => 'Low stock',
                'detail' => 'Active alerts at or below reorder point',
            ],
            'criticalStockCount' => [
                'value' => (string) $criticalCount,
                'label' => 'Critical stock',
                'detail' => 'Active alerts with immediate stockout risk',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildLowStock(int $branchId): array
    {
        return RestockingAlert::query()
            ->whereHas('reorderPolicy', fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', 'active')
            ->with('reorderPolicy.product')
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
            ->orderBy('available_quantity_snapshot')
            ->limit(10)
            ->get()
            ->map(fn (RestockingAlert $alert) => [
                'id' => (string) $alert->id,
                'productId' => (string) $alert->reorderPolicy->product_id,
                'productSku' => $alert->reorderPolicy->product->sku,
                'productName' => $alert->reorderPolicy->product->name,
                'availableQuantity' => (string) $alert->available_quantity_snapshot,
                'reorderPointQuantity' => (string) $alert->reorder_point_snapshot,
                'severity' => $alert->severity,
            ])
            ->all();
    }

    /**
     * @return array{count: int, items: array<int, array<string, mixed>>}
     */
    private function buildPendingPurchaseOrders(int $branchId): array
    {
        $query = PurchaseOrder::query()
            ->where('branch_id', $branchId)
            ->whereIn('status', ['submitted', 'approved', 'ordered']);

        $count = $query->count();

        $items = (clone $query)
            ->with('supplier')
            ->orderBy('created_at')
            ->limit(5)
            ->get()
            ->map(fn (PurchaseOrder $po) => [
                'id' => (string) $po->id,
                'poNumber' => $po->po_number,
                'supplierName' => $po->supplier->legal_name,
                'status' => $po->status,
                'totalAmount' => (string) $po->total_amount,
            ])
            ->all();

        return ['count' => $count, 'items' => $items];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildRecentSales(int $branchId): array
    {
        return Sale::query()
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->with('cashier')
            ->orderByDesc('sold_at')
            ->limit(5)
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => (string) $sale->id,
                'saleNumber' => $sale->sale_number,
                'totalAmount' => (string) $sale->total_amount,
                'soldAt' => $sale->sold_at?->toIso8601String(),
                'cashierName' => $sale->cashier?->display_name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{date: string, totalAmount: string, saleCount: int}>
     */
    private function buildSalesTrend(int $branchId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = Sale::query()
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$from->startOfDay(), $to->endOfDay()])
            ->select([
                DB::raw('DATE(sold_at) as saleDate'),
                DB::raw('SUM(total_amount) as totalAmount'),
                DB::raw('COUNT(*) as saleCount'),
            ])
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderBy('saleDate')
            ->get();

        $byDate = $rows->keyBy(fn ($row) => (string) $row->saleDate);

        $trend = [];
        for ($cursor = $from->startOfDay(); $cursor->lte($to); $cursor = $cursor->addDay()) {
            $key = $cursor->format('Y-m-d');
            $row = $byDate->get($key);

            $trend[] = [
                'date' => $key,
                'totalAmount' => $row ? (string) $row->totalAmount : '0.0000',
                'saleCount' => $row ? (int) $row->saleCount : 0,
            ];
        }

        return $trend;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildForecastSummary(int $branchId): ?array
    {
        $run = ForecastRun::query()
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->withCount('items')
            ->orderByDesc('created_at')
            ->first();

        if ($run === null) {
            return null;
        }

        $sufficientCount = $run->items()->where('cold_start_status', 'sufficient_history')->count();
        $totalCount = $run->items_count;

        return [
            'forecastRunId' => (string) $run->id,
            'modelCode' => $run->model_code,
            'periodGrain' => $run->period_grain,
            'generatedAt' => $run->completed_at?->toIso8601String() ?? $run->created_at?->toIso8601String(),
            'totalProductCount' => $totalCount,
            'sufficientHistoryCount' => $sufficientCount,
            'coverageRatio' => $totalCount > 0 ? round($sufficientCount / $totalCount, 4) : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSyncHealth(int $branchId): array
    {
        $counts = SyncOperation::query()
            ->where('branch_id', $branchId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $lastReceivedAt = SyncOperation::query()
            ->where('branch_id', $branchId)
            ->orderByDesc('received_at')
            ->value('received_at');

        return [
            'pendingCount' => (int) (($counts['received'] ?? 0) + ($counts['processing'] ?? 0) + ($counts['pending_dependency'] ?? 0)),
            'conflictedCount' => (int) ($counts['conflicted'] ?? 0),
            'rejectedCount' => (int) ($counts['rejected'] ?? 0),
            'acceptedCount' => (int) ($counts['accepted'] ?? 0),
            'lastReceivedAt' => $lastReceivedAt ? CarbonImmutable::parse($lastReceivedAt)->toIso8601String() : null,
        ];
    }
}
