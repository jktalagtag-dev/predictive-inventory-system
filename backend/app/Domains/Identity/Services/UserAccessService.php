<?php

namespace App\Domains\Identity\Services;

use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Owns user-provisioning workflows: creation and access-assignment
 * updates, including the invariant that the last active Owner cannot be
 * removed or deactivated.
 */
class UserAccessService
{
    public function createUser(array $attributes, array $roleIds, array $branchIds, int $defaultBranchId, User $actor): User
    {
        return DB::transaction(function () use ($attributes, $roleIds, $branchIds, $defaultBranchId, $actor) {
            $user = User::query()->create([...$attributes, 'row_version' => 1]);
            $user->refresh();

            $this->syncRoles($user, $roleIds, $actor);
            $this->syncBranches($user, $branchIds, $defaultBranchId, $actor);

            return $user->load(['roles', 'branches']);
        });
    }

    public function updateUser(User $user, array $attributes, ?array $roleIds, ?array $branchIds, ?int $defaultBranchId, User $actor): User
    {
        return DB::transaction(function () use ($user, $attributes, $roleIds, $branchIds, $defaultBranchId, $actor) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            $willDeactivate = array_key_exists('is_active', $attributes) && $attributes['is_active'] === false;
            $isRemovingOwnerRole = $roleIds !== null && ! in_array($this->ownerRoleId(), $roleIds, true);

            if (($willDeactivate || $isRemovingOwnerRole) && $lockedUser->hasRole('owner') && $this->isLastActiveOwner($lockedUser)) {
                throw new UserAccessException(
                    'LAST_OWNER_PROTECTED',
                    'The last active Owner cannot be deactivated or have the Owner role removed.',
                );
            }

            $lockedUser->fill($attributes);
            $lockedUser->row_version = $lockedUser->row_version + 1;
            $lockedUser->save();

            if ($roleIds !== null) {
                $this->syncRoles($lockedUser, $roleIds, $actor);
            }

            if ($branchIds !== null) {
                $this->syncBranches($lockedUser, $branchIds, $defaultBranchId ?? $branchIds[0], $actor);
            }

            return $lockedUser->load(['roles', 'branches']);
        });
    }

    private function syncRoles(User $user, array $roleIds, User $actor): void
    {
        $now = now();
        $pivotData = collect($roleIds)->mapWithKeys(fn ($roleId) => [
            $roleId => ['effective_from' => $now, 'granted_by_user_id' => $actor->id, 'created_at' => $now],
        ])->all();

        $user->roles()->sync($pivotData);
    }

    private function syncBranches(User $user, array $branchIds, int $defaultBranchId, User $actor): void
    {
        $now = now();
        $pivotData = collect($branchIds)->mapWithKeys(fn ($branchId) => [
            $branchId => [
                'is_default' => $branchId === $defaultBranchId,
                'granted_by_user_id' => $actor->id,
                'created_at' => $now,
            ],
        ])->all();

        $user->branches()->sync($pivotData);
    }

    private function ownerRoleId(): int
    {
        return Role::query()->where('code', 'owner')->value('id');
    }

    private function isLastActiveOwner(User $user): bool
    {
        $activeOwnerCount = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('code', 'owner'))
            ->count();

        return $activeOwnerCount <= 1;
    }
}
