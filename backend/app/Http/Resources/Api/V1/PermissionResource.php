<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Identity\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Permission $resource
 */
class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $permission = $this->resource;

        return [
            'id' => (string) $permission->id,
            'code' => $permission->code,
            'name' => $permission->name,
            'module' => $permission->module,
            'description' => $permission->description,
        ];
    }
}
