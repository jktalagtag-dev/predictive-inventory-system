<?php

namespace App\Domains\Planning\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Planning\Models\ReorderPolicy;

class ReorderPolicyPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('planning.rop.read');
    }

    public function view(User $actor, ReorderPolicy $policy): bool
    {
        return $actor->hasPermission('planning.rop.read') && $actor->canAccessBranch($policy->branch_id);
    }

    public function create(User $actor, int $branchId): bool
    {
        return $actor->hasPermission('planning.rop.manage') && $actor->canAccessBranch($branchId);
    }

    public function update(User $actor, ReorderPolicy $policy): bool
    {
        return $actor->hasPermission('planning.rop.manage') && $actor->canAccessBranch($policy->branch_id);
    }

    public function recalculate(User $actor, ReorderPolicy $policy): bool
    {
        return $actor->hasPermission('planning.rop.calculate') && $actor->canAccessBranch($policy->branch_id);
    }

    public function calculateEoq(User $actor, ReorderPolicy $policy): bool
    {
        return $actor->hasPermission('planning.eoq.calculate') && $actor->canAccessBranch($policy->branch_id);
    }

    public function viewEoq(User $actor, ReorderPolicy $policy): bool
    {
        return $actor->hasPermission('planning.eoq.read') && $actor->canAccessBranch($policy->branch_id);
    }
}
