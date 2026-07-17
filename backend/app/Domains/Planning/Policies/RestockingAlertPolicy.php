<?php

namespace App\Domains\Planning\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Planning\Models\RestockingAlert;

class RestockingAlertPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('restocking.read');
    }

    public function view(User $actor, RestockingAlert $alert): bool
    {
        return $actor->hasPermission('restocking.read') && $actor->canAccessBranch($alert->reorderPolicy->branch_id);
    }

    public function acknowledge(User $actor, RestockingAlert $alert): bool
    {
        return $actor->hasPermission('restocking.acknowledge') && $actor->canAccessBranch($alert->reorderPolicy->branch_id);
    }

    public function resolve(User $actor, RestockingAlert $alert): bool
    {
        return $actor->hasPermission('restocking.resolve') && $actor->canAccessBranch($alert->reorderPolicy->branch_id);
    }
}
