<?php

namespace App\Domains\Planning\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Planning\Models\ForecastRun;

class ForecastRunPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('forecasting.read');
    }

    public function view(User $actor, ForecastRun $run): bool
    {
        return $actor->hasPermission('forecasting.read') && ($run->branch_id === null || $actor->canAccessBranch($run->branch_id));
    }

    public function create(User $actor, int $branchId): bool
    {
        return $actor->hasPermission('forecasting.run') && $actor->canAccessBranch($branchId);
    }

    public function override(User $actor, ForecastRun $run): bool
    {
        return $actor->hasPermission('forecasting.override') && ($run->branch_id === null || $actor->canAccessBranch($run->branch_id));
    }
}
