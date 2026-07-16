<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Identity\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Branch $resource
 */
class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $branch = $this->resource;

        return [
            'id' => (string) $branch->id,
            'code' => $branch->code,
            'name' => $branch->name,
            'addressLine1' => $branch->address_line_1,
            'addressLine2' => $branch->address_line_2,
            'city' => $branch->city,
            'province' => $branch->province,
            'postalCode' => $branch->postal_code,
            'countryCode' => $branch->country_code,
            'phone' => $branch->phone,
            'isActive' => (bool) $branch->is_active,
            'version' => $branch->row_version,
        ];
    }
}
