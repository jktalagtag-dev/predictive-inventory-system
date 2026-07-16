<?php

namespace App\Domains\Identity\Policies;

use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\User;

class BranchPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('branches.read');
    }

    public function view(User $actor, Branch $branch): bool
    {
        return $actor->hasPermission('branches.read');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission('branches.create') && $actor->hasRole('owner');
    }

    public function update(User $actor, Branch $branch): bool
    {
        return $actor->hasPermission('branches.update') && $actor->hasRole('owner');
    }
}
