<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Models\Category;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function create(array $attributes, User $actor): Category
    {
        return DB::transaction(function () use ($attributes, $actor) {
            $this->assertCodeAvailable($attributes['code']);

            $category = Category::query()->create([
                ...$attributes,
                'row_version' => 1,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            return $category->refresh();
        });
    }

    public function update(Category $category, array $attributes, User $actor): Category
    {
        return DB::transaction(function () use ($category, $attributes, $actor) {
            $locked = Category::query()->lockForUpdate()->findOrFail($category->id);

            if (array_key_exists('code', $attributes) && $attributes['code'] !== $locked->code) {
                $this->assertCodeAvailable($attributes['code']);
            }

            if (array_key_exists('parent_category_id', $attributes)) {
                $this->assertNoCycle($locked, $attributes['parent_category_id']);
            }

            $locked->fill($attributes);
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            return $locked;
        });
    }

    public function archive(Category $category, User $actor): void
    {
        DB::transaction(function () use ($category, $actor) {
            $locked = Category::query()->lockForUpdate()->findOrFail($category->id);
            $locked->deleted_by_user_id = $actor->id;
            $locked->is_active = false;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();
            $locked->delete();
        });
    }

    private function assertCodeAvailable(string $code): void
    {
        if (Category::query()->withTrashed()->where('code', $code)->exists()) {
            throw new CategoryException('DUPLICATE_CATEGORY_CODE', 409, 'A category with this code already exists.');
        }
    }

    private function assertNoCycle(Category $category, ?int $newParentId): void
    {
        if ($newParentId === null) {
            return;
        }

        if ($newParentId === $category->id) {
            throw new CategoryException('INVALID_PARENT_CATEGORY', 422, 'A category cannot be its own parent.');
        }

        $ancestorId = $newParentId;
        $visited = [];

        while ($ancestorId !== null) {
            if ($ancestorId === $category->id || isset($visited[$ancestorId])) {
                throw new CategoryException('INVALID_PARENT_CATEGORY', 422, 'This parent assignment would create a category cycle.');
            }

            $visited[$ancestorId] = true;
            $ancestorId = Category::query()->withTrashed()->where('id', $ancestorId)->value('parent_category_id');
        }
    }
}
