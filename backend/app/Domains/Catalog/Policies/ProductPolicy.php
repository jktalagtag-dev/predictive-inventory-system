<?php

namespace App\Domains\Catalog\Policies;

use App\Domains\Catalog\Models\Product;
use App\Domains\Identity\Models\User;

class ProductPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('products.read');
    }

    public function view(User $actor, Product $product): bool
    {
        return $actor->hasPermission('products.read');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission('products.create');
    }

    public function update(User $actor, Product $product): bool
    {
        return $actor->hasPermission('products.update');
    }

    public function delete(User $actor, Product $product): bool
    {
        return $actor->hasPermission('products.delete');
    }
}
