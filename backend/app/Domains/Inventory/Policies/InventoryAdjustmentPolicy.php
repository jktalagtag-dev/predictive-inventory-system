<?php

namespace App\Domains\Inventory\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\InventoryAdjustment;

class InventoryAdjustmentPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('inventory.adjustments.read');
    }

    public function view(User $actor, InventoryAdjustment $adjustment): bool
    {
        return $actor->hasPermission('inventory.adjustments.read') && $actor->canAccessBranch($adjustment->branch_id);
    }

    public function create(User $actor, int $branchId): bool
    {
        return $actor->hasPermission('inventory.adjustments.create') && $actor->canAccessBranch($branchId);
    }

    public function update(User $actor, InventoryAdjustment $adjustment): bool
    {
        return $actor->hasPermission('inventory.adjustments.update') && $actor->canAccessBranch($adjustment->branch_id);
    }

    public function approve(User $actor, InventoryAdjustment $adjustment): bool
    {
        return $actor->hasPermission('inventory.adjustments.approve') && $actor->canAccessBranch($adjustment->branch_id);
    }

    public function post(User $actor, InventoryAdjustment $adjustment): bool
    {
        return $actor->hasPermission('inventory.adjustments.post') && $actor->canAccessBranch($adjustment->branch_id);
    }

    public function reverse(User $actor, InventoryAdjustment $adjustment): bool
    {
        return $actor->hasPermission('inventory.adjustments.reverse') && $actor->canAccessBranch($adjustment->branch_id);
    }
}
