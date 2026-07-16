<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Models\Product;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function create(array $attributes, User $actor): Product
    {
        return DB::transaction(function () use ($attributes, $actor) {
            $this->assertSkuAvailable($attributes['sku']);
            if (! empty($attributes['barcode'])) {
                $this->assertBarcodeAvailable($attributes['barcode']);
            }

            $product = Product::query()->create([
                ...$attributes,
                'row_version' => 1,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            return $product->refresh();
        });
    }

    public function update(Product $product, array $attributes, User $actor): Product
    {
        return DB::transaction(function () use ($product, $attributes, $actor) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);

            if (array_key_exists('sku', $attributes) && $attributes['sku'] !== $locked->sku) {
                $this->assertSkuAvailable($attributes['sku']);
            }

            if (array_key_exists('barcode', $attributes) && $attributes['barcode'] !== null && $attributes['barcode'] !== $locked->barcode) {
                $this->assertBarcodeAvailable($attributes['barcode']);
            }

            $locked->fill($attributes);
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            return $locked;
        });
    }

    public function archive(Product $product, User $actor): void
    {
        DB::transaction(function () use ($product, $actor) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $locked->deleted_by_user_id = $actor->id;
            $locked->is_active = false;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();
            $locked->delete();
        });
    }

    private function assertSkuAvailable(string $sku): void
    {
        if (Product::query()->withTrashed()->where('sku', $sku)->exists()) {
            throw new ProductException('DUPLICATE_SKU', 409, 'A product with this SKU already exists.');
        }
    }

    private function assertBarcodeAvailable(string $barcode): void
    {
        if (Product::query()->withTrashed()->where('barcode', $barcode)->exists()) {
            throw new ProductException('DUPLICATE_BARCODE', 409, 'A product with this barcode already exists.');
        }
    }
}
