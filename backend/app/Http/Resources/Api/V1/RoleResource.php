<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Identity\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Role $resource
 */
class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->resource;

        return [
            'id' => (string) $role->id,
            'code' => $role->code,
            'name' => $role->name,
            'description' => $role->description,
            'isSystemRole' => (bool) $role->is_system_role,
            'permissions' => $role->relationLoaded('permissions')
                ? $role->permissions->pluck('code')->values()
                : [],
        ];
    }
}
