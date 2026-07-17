<?php

namespace App\Domains\Inventory\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\GoodsReceipt;

class GoodsReceiptPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('goods_receipts.read');
    }

    public function view(User $actor, GoodsReceipt $receipt): bool
    {
        return $actor->hasPermission('goods_receipts.read') && $actor->canAccessBranch($receipt->branch_id);
    }

    public function create(User $actor, int $branchId): bool
    {
        return $actor->hasPermission('goods_receipts.create') && $actor->canAccessBranch($branchId);
    }

    public function update(User $actor, GoodsReceipt $receipt): bool
    {
        return $actor->hasPermission('goods_receipts.update') && $actor->canAccessBranch($receipt->branch_id);
    }

    public function post(User $actor, GoodsReceipt $receipt): bool
    {
        return $actor->hasPermission('goods_receipts.post') && $actor->canAccessBranch($receipt->branch_id);
    }

    public function reverse(User $actor, GoodsReceipt $receipt): bool
    {
        return $actor->hasPermission('goods_receipts.reverse') && $actor->canAccessBranch($receipt->branch_id);
    }
}
