<?php

namespace App\Domains\Reporting\Support;

use App\Domains\Identity\Models\User;
use Illuminate\Support\Collection;

/**
 * The code-defined registry of runnable reports. Each definition names the
 * permission that gates it, its accepted filters, and its export formats;
 * ReportRunner and ReportExportService both read from here so the catalog,
 * the interactive endpoint, and export generation can never drift apart.
 */
final class ReportCatalog
{
    /**
     * @return array<string, ReportDefinition>
     */
    public static function all(): array
    {
        return [
            'inventory-on-hand' => new ReportDefinition(
                code: 'inventory-on-hand',
                title: 'Inventory on hand',
                description: 'Current on-hand, reserved, available, and incoming stock by product and branch.',
                permission: 'inventory.read',
                formats: ['pdf', 'csv', 'xlsx'],
                filters: [
                    'branchId' => ['type' => 'integer', 'required' => true],
                    'categoryId' => ['type' => 'integer', 'required' => false],
                    'isActive' => ['type' => 'boolean', 'required' => false],
                ],
                columns: ['sku', 'productName', 'categoryName', 'onHandQuantity', 'reservedQuantity', 'availableQuantity', 'incomingQuantity'],
                dataClassification: 'internal',
            ),
            'low-stock' => new ReportDefinition(
                code: 'low-stock',
                title: 'Low stock and reorder exposure',
                description: 'Active reorder policies whose available stock has reached or fallen below the reorder point.',
                permission: 'restocking.read',
                formats: ['pdf', 'csv', 'xlsx'],
                filters: [
                    'branchId' => ['type' => 'integer', 'required' => true],
                ],
                columns: ['sku', 'productName', 'availableQuantity', 'reorderPointQuantity', 'safetyStockQuantity', 'ropCalculatedAt'],
                dataClassification: 'internal',
            ),
            'sales-summary' => new ReportDefinition(
                code: 'sales-summary',
                title: 'Sales summary',
                description: 'Completed sales totals by day for a branch and date range, net of voids and refunds.',
                permission: 'sales.read',
                formats: ['pdf', 'csv', 'xlsx'],
                filters: [
                    'branchId' => ['type' => 'integer', 'required' => true],
                    'dateFrom' => ['type' => 'date', 'required' => true],
                    'dateTo' => ['type' => 'date', 'required' => true],
                ],
                columns: ['saleDate', 'completedSalesCount', 'voidedSalesCount', 'refundedSalesCount', 'grossSalesAmount', 'netSalesAmount'],
                dataClassification: 'confidential',
            ),
            'purchase-order-status' => new ReportDefinition(
                code: 'purchase-order-status',
                title: 'Purchase order status',
                description: 'Purchase orders and their current workflow status for a branch and date range.',
                permission: 'purchase_orders.read',
                formats: ['pdf', 'csv', 'xlsx'],
                filters: [
                    'branchId' => ['type' => 'integer', 'required' => true],
                    'dateFrom' => ['type' => 'date', 'required' => false],
                    'dateTo' => ['type' => 'date', 'required' => false],
                    'status' => ['type' => 'string', 'required' => false],
                ],
                columns: ['poNumber', 'supplierName', 'status', 'orderedAt', 'expectedReceiptAt', 'totalAmount'],
                dataClassification: 'confidential',
            ),
        ];
    }

    public static function find(string $code): ?ReportDefinition
    {
        return self::all()[$code] ?? null;
    }

    /**
     * @return Collection<int, ReportDefinition>
     */
    public static function visibleTo(User $actor): Collection
    {
        return collect(self::all())->filter(fn (ReportDefinition $definition) => $actor->hasPermission($definition->permission))->values();
    }
}
