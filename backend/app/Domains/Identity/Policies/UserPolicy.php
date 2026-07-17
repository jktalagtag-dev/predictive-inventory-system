<?php

namespace App\Domains\Identity\Policies;

use App\Domains\Identity\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('users.read');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->hasPermission('users.read');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission('users.create');
    }

    public function update(User $actor, User $target): bool
    {
        if (! $actor->hasPermission('users.update')) {
            return false;
        }

        // Structural guard, not just a permission-grant assumption:
        // CLAUDE.md section 6 requires this to hold even if a future
        // change ever grants users.update outside the owner role.
        if ($target->hasRole('owner') && ! $actor->hasRole('owner')) {
            return false;
        }

        return true;
    }
}
