<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Catalog\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property UnitOfMeasure $resource
 */
class UnitOfMeasureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $unit = $this->resource;

        return [
            'id' => (string) $unit->id,
            'code' => $unit->code,
            'name' => $unit->name,
            'symbol' => $unit->symbol,
            'dimension' => $unit->dimension,
            'isActive' => (bool) $unit->is_active,
            'version' => $unit->row_version,
        ];
    }
}
