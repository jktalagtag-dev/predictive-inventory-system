<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $sku = strtoupper(fake()->unique()->bothify('SHX-???-###'));

        return [
            'category_id' => Category::query()->inRandomOrder()->value('id') ?? Category::factory(),
            'stock_unit_id' => UnitOfMeasure::query()->where('code', 'EA')->value('id')
                ?? UnitOfMeasure::query()->value('id'),
            'sku' => $sku,
            'barcode' => null,
            'name' => ucwords(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'image_url' => "https://picsum.photos/seed/{$sku}/600/600",
            'product_type' => 'stock',
            'is_active' => true,
            'is_lot_tracked' => false,
            'is_serial_tracked' => false,
            'is_expiry_tracked' => false,
            'default_tax_rate' => '12.0000',
            'selling_price' => (string) fake()->randomFloat(4, 50, 5000),
            'default_lead_time_days' => (string) fake()->numberBetween(3, 21),
            'row_version' => 1,
        ];
    }
}
