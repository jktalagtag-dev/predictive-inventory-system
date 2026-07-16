<?php

namespace App\Domains\Catalog\Policies;

use App\Domains\Catalog\Models\Category;
use App\Domains\Identity\Models\User;

class CategoryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('categories.read');
    }

    public function view(User $actor, Category $category): bool
    {
        return $actor->hasPermission('categories.read');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission('categories.create');
    }

    public function update(User $actor, Category $category): bool
    {
        return $actor->hasPermission('categories.update');
    }

    public function delete(User $actor, Category $category): bool
    {
        return $actor->hasPermission('categories.delete');
    }
}
