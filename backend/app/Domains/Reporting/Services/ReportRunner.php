<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Planning\Models\ReorderPolicy;
use App\Domains\Procurement\Models\PurchaseOrder;
use App\Domains\Reporting\Support\ReportCatalog;
use App\Domains\Reporting\Support\ReportDefinition;
use App\Domains\Sales\Models\Sale;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Server-side aggregation for the interactive report catalog. Every report
 * derives from authoritative transactional tables at request time — none
 * of this is a cached or precomputed summary field (CLAUDE.md section 54,
 * "Reconcile inventory and sales reports against source transactions").
 */
class ReportRunner
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{columns: string[], rows: array<int, array<string, mixed>>, aggregates: array<string, mixed>, meta: array<string, mixed>}
     */
    public function run(string $reportCode, array $filters, User $actor): array
    {
        $definition = ReportCatalog::find($reportCode);

        if ($definition === null) {
            throw new ReportException('UNKNOWN_REPORT', 404, 'This report is not in the catalog.');
        }

        if (! $actor->hasPermission($definition->permission)) {
            throw new ReportException('FORBIDDEN', 403, 'You are not authorized to run this report.');
        }

        $this->assertRequiredFilters($definition, $filters);

        if (array_key_exists('branchId', $filters) && ! $actor->canAccessBranch((int) $filters['branchId'])) {
            throw new ReportException('INVALID_REPORT_FILTER', 422, 'You are not authorized for that branch.');
        }

        $generatedAt = CarbonImmutable::now();

        $result = match ($reportCode) {
            'inventory-on-hand' => $this->runInventoryOnHand($filters),
            'low-stock' => $this->runLowStock($filters),
            'sales-summary' => $this->runSalesSummary($filters),
            'purchase-order-status' => $this->runPurchaseOrderStatus($filters),
            default => throw new ReportException('UNKNOWN_REPORT', 404, 'This report is not in the catalog.'),
        };

        // Aggregates always reflect the full filtered result set; only the
        // row listing itself is paginated for interactive viewing, so a
        // report's totals never silently change based on which page a
        // user happens to be looking at.
        $allRows = $result['rows'];
        $perPage = min(max((int) ($filters['perPage'] ?? 50), 1), 500);
        $page = max((int) ($filters['page'] ?? 1), 1);
        $pagedRows = array_slice($allRows, ($page - 1) * $perPage, $perPage);

        return [
            'columns' => $definition->columns,
            'rows' => array_values($pagedRows),
            'aggregates' => $result['aggregates'],
            'meta' => [
                'reportCode' => $reportCode,
                'filterSummary' => $filters,
                'timezone' => config('app.timezone'),
                'currency' => 'PHP',
                'generatedAt' => $generatedAt->toIso8601String(),
                'dataCutoffAt' => $generatedAt->toIso8601String(),
                'freshness' => 'live',
                'accessClassification' => $definition->dataClassification,
                'page' => $page,
                'perPage' => $perPage,
                'totalRows' => count($allRows),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function sumDecimal(array $rows, string $key): string
    {
        $total = '0.0000';
        foreach ($rows as $row) {
            $total = bcadd($total, (string) $row[$key], 4);
        }

        return $total;
    }

    private function assertRequiredFilters(ReportDefinition $definition, array $filters): void
    {
        foreach ($definition->filters as $key => $spec) {
            if ($spec['required'] && ! array_key_exists($key, $filters)) {
                throw new ReportException('INVALID_REPORT_FILTER', 422, "The {$key} filter is required for this report.");
            }
        }
    }

    private function runInventoryOnHand(array $filters): array
    {
        $query = InventoryBalance::query()
            ->join('products', 'products.id', '=', 'inventory_balances.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('inventory_balances.branch_id', (int) $filters['branchId']);

        if (array_key_exists('categoryId', $filters)) {
            $query->where('products.category_id', (int) $filters['categoryId']);
        }

        if (array_key_exists('isActive', $filters)) {
            $query->where('products.is_active', (bool) $filters['isActive']);
        }

        $rows = $query
            ->orderBy('products.name')
            ->get([
                'products.sku as sku',
                'products.name as productName',
                'categories.name as categoryName',
                'inventory_balances.on_hand_quantity as onHandQuantity',
                'inventory_balances.reserved_quantity as reservedQuantity',
                'inventory_balances.available_quantity as availableQuantity',
                'inventory_balances.incoming_quantity as incomingQuantity',
            ])
            ->map(fn ($row) => $row->toArray())
            ->all();

        return [
            'rows' => $rows,
            'aggregates' => [
                'productCount' => count($rows),
                'totalOnHandQuantity' => $this->sumDecimal($rows, 'onHandQuantity'),
                'totalAvailableQuantity' => $this->sumDecimal($rows, 'availableQuantity'),
            ],
        ];
    }

    private function runLowStock(array $filters): array
    {
        $rows = ReorderPolicy::query()
            ->join('products', 'products.id', '=', 'reorder_policies.product_id')
            ->join('inventory_balances', function ($join) {
                $join->on('inventory_balances.product_id', '=', 'reorder_policies.product_id')
                    ->on('inventory_balances.branch_id', '=', 'reorder_policies.branch_id');
            })
            ->where('reorder_policies.branch_id', (int) $filters['branchId'])
            ->where('reorder_policies.is_active', true)
            ->whereNotNull('reorder_policies.reorder_point_quantity')
            ->whereColumn('inventory_balances.available_quantity', '<=', 'reorder_policies.reorder_point_quantity')
            ->orderBy('products.name')
            ->get([
                'products.sku as sku',
                'products.name as productName',
                'inventory_balances.available_quantity as availableQuantity',
                'reorder_policies.reorder_point_quantity as reorderPointQuantity',
                'reorder_policies.safety_stock_quantity as safetyStockQuantity',
                'reorder_policies.rop_calculated_at as ropCalculatedAt',
            ])
            ->map(fn ($row) => $row->toArray())
            ->all();

        return [
            'rows' => $rows,
            'aggregates' => ['productsAtOrBelowRop' => count($rows)],
        ];
    }

    private function runSalesSummary(array $filters): array
    {
        $from = CarbonImmutable::parse($filters['dateFrom'])->startOfDay();
        $to = CarbonImmutable::parse($filters['dateTo'])->endOfDay();

        if ($from->greaterThan($to)) {
            throw new ReportException('INVALID_REPORT_FILTER', 422, 'dateFrom must not be after dateTo.');
        }

        $rows = Sale::query()
            ->where('branch_id', (int) $filters['branchId'])
            ->whereBetween('sold_at', [$from, $to])
            ->select([
                DB::raw('DATE(sold_at) as saleDate'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completedSalesCount"),
                DB::raw("SUM(CASE WHEN status = 'voided' THEN 1 ELSE 0 END) as voidedSalesCount"),
                DB::raw("SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END) as refundedSalesCount"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as grossSalesAmount"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN total_amount WHEN status = 'refunded' THEN -total_amount ELSE 0 END) as netSalesAmount"),
            ])
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderBy('saleDate')
            ->get()
            ->map(fn ($row) => $row->toArray())
            ->all();

        return [
            'rows' => $rows,
            'aggregates' => [
                'totalGrossSalesAmount' => $this->sumDecimal($rows, 'grossSalesAmount'),
                'totalNetSalesAmount' => $this->sumDecimal($rows, 'netSalesAmount'),
                'dayCount' => count($rows),
            ],
        ];
    }

    private function runPurchaseOrderStatus(array $filters): array
    {
        $query = PurchaseOrder::query()
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->where('purchase_orders.branch_id', (int) $filters['branchId']);

        if (array_key_exists('status', $filters)) {
            $query->where('purchase_orders.status', $filters['status']);
        }

        if (array_key_exists('dateFrom', $filters)) {
            $query->where('purchase_orders.created_at', '>=', CarbonImmutable::parse($filters['dateFrom'])->startOfDay());
        }

        if (array_key_exists('dateTo', $filters)) {
            $query->where('purchase_orders.created_at', '<=', CarbonImmutable::parse($filters['dateTo'])->endOfDay());
        }

        $rows = $query
            ->orderByDesc('purchase_orders.created_at')
            ->get([
                'purchase_orders.po_number as poNumber',
                'suppliers.legal_name as supplierName',
                'purchase_orders.status as status',
                'purchase_orders.ordered_at as orderedAt',
                'purchase_orders.expected_receipt_at as expectedReceiptAt',
                'purchase_orders.total_amount as totalAmount',
            ])
            ->map(fn ($row) => $row->toArray())
            ->all();

        $byStatus = [];
        foreach ($rows as $row) {
            $byStatus[$row['status']] = ($byStatus[$row['status']] ?? 0) + 1;
        }

        return [
            'rows' => $rows,
            'aggregates' => [
                'purchaseOrderCount' => count($rows),
                'countByStatus' => $byStatus,
                'totalAmount' => $this->sumDecimal($rows, 'totalAmount'),
            ],
        ];
    }
}
