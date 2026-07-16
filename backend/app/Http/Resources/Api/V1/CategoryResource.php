<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Catalog\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Category $resource
 */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $category = $this->resource;

        return [
            'id' => (string) $category->id,
            'parentCategoryId' => $category->parent_category_id ? (string) $category->parent_category_id : null,
            'parentName' => $category->relationLoaded('parent') ? optional($category->parent)->name : null,
            'code' => $category->code,
            'name' => $category->name,
            'description' => $category->description,
            'isActive' => (bool) $category->is_active,
            // Product association is not yet implemented (deferred to the
            // Catalog/Inventory milestone); reported as 0 until real.
            'productCount' => 0,
            'updatedAt' => $category->updated_at?->toIso8601String(),
            'version' => $category->row_version,
        ];
    }
}
