<?php

namespace App\Domains\Procurement\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Procurement\Models\PurchaseOrder;

class PurchaseOrderPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('purchase_orders.read');
    }

    public function view(User $actor, PurchaseOrder $purchaseOrder): bool
    {
        return $actor->hasPermission('purchase_orders.read') && $actor->canAccessBranch($purchaseOrder->branch_id);
    }

    public function create(User $actor, int $branchId): bool
    {
        return $actor->hasPermission('purchase_orders.create') && $actor->canAccessBranch($branchId);
    }

    public function update(User $actor, PurchaseOrder $purchaseOrder): bool
    {
        return $actor->hasPermission('purchase_orders.update') && $actor->canAccessBranch($purchaseOrder->branch_id);
    }

    public function submit(User $actor, PurchaseOrder $purchaseOrder): bool
    {
        return $actor->hasPermission('purchase_orders.submit') && $actor->canAccessBranch($purchaseOrder->branch_id);
    }

    public function approve(User $actor, PurchaseOrder $purchaseOrder): bool
    {
        return $actor->hasPermission('purchase_orders.approve') && $actor->canAccessBranch($purchaseOrder->branch_id);
    }

    public function markOrdered(User $actor, PurchaseOrder $purchaseOrder): bool
    {
        return $actor->hasPermission('purchase_orders.order') && $actor->canAccessBranch($purchaseOrder->branch_id);
    }

    public function cancel(User $actor, PurchaseOrder $purchaseOrder): bool
    {
        return $actor->hasPermission('purchase_orders.cancel') && $actor->canAccessBranch($purchaseOrder->branch_id);
    }

    public function close(User $actor, PurchaseOrder $purchaseOrder): bool
    {
        return $actor->hasPermission('purchase_orders.close') && $actor->canAccessBranch($purchaseOrder->branch_id);
    }
}
