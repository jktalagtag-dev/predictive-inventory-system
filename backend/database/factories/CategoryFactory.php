<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'parent_category_id' => null,
            'code' => strtoupper(fake()->unique()->lexify('CAT-????')),
            'name' => ucwords($name),
            'description' => fake()->sentence(),
            'is_active' => true,
            'row_version' => 1,
        ];
    }
}
