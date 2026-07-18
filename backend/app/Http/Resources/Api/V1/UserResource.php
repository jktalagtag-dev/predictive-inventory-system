<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property User $resource
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource;

        return [
            'id' => (string) $user->id,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'displayName' => $user->display_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatarUrl' => $user->avatar_url,
            'isActive' => (bool) $user->is_active,
            'roles' => $user->roles->map(fn ($role) => [
                'id' => (string) $role->id,
                'code' => $role->code,
                'name' => $role->name,
            ])->values(),
            'branches' => $user->branches->map(fn ($branch) => [
                'id' => (string) $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'isDefault' => (bool) $branch->pivot->is_default,
            ])->values(),
            'lastLoginAt' => optional($user->last_login_at)->toIso8601String(),
            'version' => $user->row_version,
        ];
    }
}
