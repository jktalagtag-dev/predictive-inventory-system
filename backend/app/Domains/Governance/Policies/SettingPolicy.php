<?php

namespace App\Domains\Governance\Policies;

use App\Domains\Identity\Models\User;

/**
 * Setting rows are never route-model-bound (the route key is a
 * dot-namespaced string, not an ID resolved from the settings table), so
 * this policy only gates by permission; the owner-only enforcement for
 * individual policy/security keys lives in SettingsService, which knows
 * per-key ownership (REST_API_SPECIFICATION.md section 15.2).
 */
class SettingPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('settings.read');
    }

    public function manage(User $actor): bool
    {
        return $actor->hasPermission('settings.manage');
    }
}
