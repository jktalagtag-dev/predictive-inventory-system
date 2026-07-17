<?php

namespace Database\Seeders;

use App\Domains\Identity\Models\Permission;
use App\Domains\Identity\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Permission code => [module, display name].
     */
    public const PERMISSIONS = [
        'dashboard.read' => ['dashboard', 'View operational dashboard'],
        'users.read' => ['identity', 'View users'],
        'users.create' => ['identity', 'Create users'],
        'users.update' => ['identity', 'Update users'],
        'roles.read' => ['identity', 'View roles'],
        'permissions.read' => ['identity', 'View permissions'],
        'branches.read' => ['identity', 'View branches'],
        'branches.create' => ['identity', 'Create branches'],
        'branches.update' => ['identity', 'Update branches'],
        'categories.read' => ['catalog', 'View categories'],
        'categories.create' => ['catalog', 'Create categories'],
        'categories.update' => ['catalog', 'Update categories'],
        'categories.delete' => ['catalog', 'Archive categories'],
        'units.read' => ['catalog', 'View units of measure'],
        'units.manage' => ['catalog', 'Manage units of measure'],
        'products.read' => ['catalog', 'View products'],
        'products.create' => ['catalog', 'Create products'],
        'products.update' => ['catalog', 'Update products'],
        'products.delete' => ['catalog', 'Archive products'],
        'inventory.read' => ['inventory', 'View inventory balances'],
        'inventory.movements.read' => ['inventory', 'View inventory movement history'],
        'inventory.adjustments.read' => ['inventory', 'View inventory adjustments'],
        'inventory.adjustments.create' => ['inventory', 'Create inventory adjustment drafts'],
        'inventory.adjustments.update' => ['inventory', 'Update inventory adjustment drafts'],
        'inventory.adjustments.approve' => ['inventory', 'Approve inventory adjustments'],
        'inventory.adjustments.post' => ['inventory', 'Post inventory adjustments'],
        'inventory.adjustments.reverse' => ['inventory', 'Reverse posted inventory adjustments'],
        'suppliers.read' => ['procurement', 'View suppliers'],
        'suppliers.create' => ['procurement', 'Create suppliers'],
        'suppliers.update' => ['procurement', 'Update suppliers'],
        'purchase_orders.read' => ['procurement', 'View purchase orders'],
        'purchase_orders.create' => ['procurement', 'Create purchase order drafts'],
        'purchase_orders.update' => ['procurement', 'Update purchase order drafts'],
        'purchase_orders.submit' => ['procurement', 'Submit purchase orders for approval'],
        'purchase_orders.approve' => ['procurement', 'Approve or reject purchase orders'],
        'purchase_orders.order' => ['procurement', 'Mark purchase orders as ordered'],
        'purchase_orders.cancel' => ['procurement', 'Cancel purchase orders'],
        'purchase_orders.close' => ['procurement', 'Close purchase orders'],
        'goods_receipts.read' => ['inventory', 'View goods receipts'],
        'goods_receipts.create' => ['inventory', 'Create goods receipt drafts'],
        'goods_receipts.update' => ['inventory', 'Update goods receipt drafts'],
        'goods_receipts.post' => ['inventory', 'Post goods receipts'],
        'goods_receipts.reverse' => ['inventory', 'Reverse posted goods receipts'],
        'audit.read' => ['governance', 'View audit trail'],
        'pos.use' => ['sales', 'Use the point-of-sale product lookup'],
        'pos.finalize' => ['sales', 'Finalize point-of-sale transactions'],
        'pos.price_override' => ['sales', 'Override the selling price at checkout'],
        'pos.discount_override' => ['sales', 'Apply a discount at checkout'],
        'sales.read' => ['sales', 'View sales history'],
        'sales.void' => ['sales', 'Void completed sales'],
        'sales.refund' => ['sales', 'Refund completed sales'],
        'forecasting.read' => ['planning', 'View forecast runs and results'],
        'forecasting.run' => ['planning', 'Run demand forecasts'],
        'forecasting.override' => ['planning', 'Record manual planning overrides'],
        'planning.eoq.read' => ['planning', 'View EOQ calculation history'],
        'planning.eoq.calculate' => ['planning', 'Calculate EOQ recommendations'],
        'planning.rop.read' => ['planning', 'View reorder policies'],
        'planning.rop.manage' => ['planning', 'Create and update reorder policies'],
        'planning.rop.calculate' => ['planning', 'Recalculate reorder points'],
        'restocking.read' => ['planning', 'View restocking alerts'],
        'restocking.acknowledge' => ['planning', 'Acknowledge restocking alerts'],
        'restocking.resolve' => ['planning', 'Resolve or dismiss restocking alerts'],
        'restocking.evaluate' => ['planning', 'Trigger restocking alert evaluation'],
        'reports.read' => ['reporting', 'View and run the report catalog'],
        'reports.export' => ['reporting', 'Request and download report exports'],
        'settings.read' => ['governance', 'View system settings'],
        'settings.manage' => ['governance', 'Change system settings'],
        'settings.read_sensitive' => ['governance', 'View sensitive system setting values'],
        'sync.use' => ['sync', 'Submit and check status of offline-queued operations'],
    ];

    /**
     * Role code => list of granted permission codes.
     */
    public const ROLE_GRANTS = [
        'owner' => [
            'dashboard.read', 'users.read', 'users.create', 'users.update',
            'roles.read', 'permissions.read', 'branches.read', 'branches.create', 'branches.update',
            'categories.read', 'categories.create', 'categories.update', 'categories.delete',
            'units.read', 'units.manage',
            'products.read', 'products.create', 'products.update', 'products.delete',
            'inventory.read', 'inventory.movements.read', 'inventory.adjustments.read',
            'inventory.adjustments.create', 'inventory.adjustments.update', 'inventory.adjustments.approve',
            'inventory.adjustments.post', 'inventory.adjustments.reverse',
            'suppliers.read', 'suppliers.create', 'suppliers.update',
            'purchase_orders.read', 'purchase_orders.create', 'purchase_orders.update', 'purchase_orders.submit',
            'purchase_orders.approve', 'purchase_orders.order', 'purchase_orders.cancel', 'purchase_orders.close',
            'goods_receipts.read', 'goods_receipts.create', 'goods_receipts.update',
            'goods_receipts.post', 'goods_receipts.reverse',
            'audit.read',
            'pos.use', 'pos.finalize', 'pos.price_override', 'pos.discount_override',
            'sales.read', 'sales.void', 'sales.refund',
            'forecasting.read', 'forecasting.run', 'forecasting.override',
            'planning.eoq.read', 'planning.eoq.calculate',
            'planning.rop.read', 'planning.rop.manage', 'planning.rop.calculate',
            'restocking.read', 'restocking.acknowledge', 'restocking.resolve', 'restocking.evaluate',
            'reports.read', 'reports.export',
            'settings.read', 'settings.manage', 'settings.read_sensitive',
            'sync.use',
        ],
        'manager' => [
            'dashboard.read', 'categories.read', 'categories.create', 'categories.update', 'categories.delete',
            'units.read', 'units.manage',
            'products.read', 'products.create', 'products.update', 'products.delete',
            'inventory.read', 'inventory.movements.read', 'inventory.adjustments.read',
            'inventory.adjustments.create', 'inventory.adjustments.update', 'inventory.adjustments.approve',
            'inventory.adjustments.post', 'inventory.adjustments.reverse',
            'suppliers.read', 'suppliers.create', 'suppliers.update',
            'purchase_orders.read', 'purchase_orders.create', 'purchase_orders.update', 'purchase_orders.submit',
            'purchase_orders.approve', 'purchase_orders.order', 'purchase_orders.cancel', 'purchase_orders.close',
            'goods_receipts.read', 'goods_receipts.create', 'goods_receipts.update',
            'goods_receipts.post', 'goods_receipts.reverse',
            'audit.read',
            'pos.use', 'pos.finalize', 'pos.price_override', 'pos.discount_override',
            'sales.read', 'sales.void', 'sales.refund',
            'forecasting.read', 'forecasting.run', 'forecasting.override',
            'planning.eoq.read', 'planning.eoq.calculate',
            'planning.rop.read', 'planning.rop.manage', 'planning.rop.calculate',
            'restocking.read', 'restocking.acknowledge', 'restocking.resolve', 'restocking.evaluate',
            'reports.read', 'reports.export',
            'settings.read', 'settings.manage',
            'sync.use',
        ],
        'staff' => [
            'dashboard.read', 'products.read', 'categories.read', 'units.read',
            'inventory.read', 'inventory.movements.read', 'inventory.adjustments.read',
            'inventory.adjustments.create',
            'suppliers.read', 'purchase_orders.read',
            'goods_receipts.read', 'goods_receipts.create',
            'pos.use', 'pos.finalize', 'sales.read',
            'forecasting.read', 'planning.rop.read', 'restocking.read', 'restocking.acknowledge',
            'sync.use',
        ],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $code => [$module, $name]) {
            Permission::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'module' => $module],
            );
        }

        foreach (self::ROLE_GRANTS as $roleCode => $permissionCodes) {
            $role = Role::query()->where('code', $roleCode)->firstOrFail();
            $permissionIds = Permission::query()->whereIn('code', $permissionCodes)->pluck('id');
            $role->permissions()->syncWithoutDetaching(
                $permissionIds->mapWithKeys(fn ($id) => [$id => ['created_at' => now()]])->all(),
            );
        }
    }
}
