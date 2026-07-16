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
        return $actor->hasPermission('users.update');
    }
}
