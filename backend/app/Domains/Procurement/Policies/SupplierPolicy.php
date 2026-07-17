<?php

namespace App\Domains\Procurement\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Procurement\Models\Supplier;

class SupplierPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('suppliers.read');
    }

    public function view(User $actor, Supplier $supplier): bool
    {
        return $actor->hasPermission('suppliers.read');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission('suppliers.create');
    }

    public function update(User $actor, Supplier $supplier): bool
    {
        return $actor->hasPermission('suppliers.update');
    }
}
