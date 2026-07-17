<?php

namespace App\Domains\Sales\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Sales\Models\Sale;

class SalePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('sales.read');
    }

    public function view(User $actor, Sale $sale): bool
    {
        return $actor->hasPermission('sales.read') && $actor->canAccessBranch($sale->branch_id);
    }

    public function create(User $actor, int $branchId): bool
    {
        return $actor->hasPermission('pos.finalize') && $actor->canAccessBranch($branchId);
    }

    public function void(User $actor, Sale $sale): bool
    {
        return $actor->hasPermission('sales.void') && $actor->canAccessBranch($sale->branch_id);
    }

    public function refund(User $actor, Sale $sale): bool
    {
        return $actor->hasPermission('sales.refund') && $actor->canAccessBranch($sale->branch_id);
    }
}
