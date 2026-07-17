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
        'audit.read' => ['governance', 'View audit trail'],
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
            'audit.read',
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
            'audit.read',
        ],
        'staff' => [
            'dashboard.read', 'products.read', 'categories.read', 'units.read',
            'inventory.read', 'inventory.movements.read', 'inventory.adjustments.read',
            'inventory.adjustments.create',
            'suppliers.read', 'purchase_orders.read',
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
