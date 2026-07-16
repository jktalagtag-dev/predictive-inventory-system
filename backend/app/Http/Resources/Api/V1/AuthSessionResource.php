<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property User $resource
 */
class AuthSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource;
        $roles = $user->activeRoles()->with('permissions')->get();
        $permissionCodes = $roles->flatMap(fn ($role) => $role->permissions->pluck('code'))->unique()->values();

        return [
            'user' => [
                'id' => (string) $user->id,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'displayName' => $user->display_name,
                'email' => $user->email,
                'roles' => $roles->pluck('code')->values(),
                'permissions' => $permissionCodes,
                'branches' => $user->branches->map(fn ($branch) => [
                    'id' => (string) $branch->id,
                    'code' => $branch->code,
                    'name' => $branch->name,
                    'isDefault' => (bool) $branch->pivot->is_default,
                ])->values(),
            ],
            'expiresAt' => $this->sessionExpiresAt(),
        ];
    }

    private function sessionExpiresAt(): ?string
    {
        $lifetimeMinutes = config('session.lifetime');

        if (! is_numeric($lifetimeMinutes)) {
            return null;
        }

        return now()->addMinutes((int) $lifetimeMinutes)->toIso8601String();
    }
}
