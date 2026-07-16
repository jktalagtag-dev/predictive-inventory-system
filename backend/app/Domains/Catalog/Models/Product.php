<?php

namespace App\Domains\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $category_id
 * @property int $stock_unit_id
 * @property string $sku
 * @property string|null $barcode
 * @property string $name
 * @property string|null $description
 * @property string $product_type
 * @property bool $is_active
 * @property bool $is_lot_tracked
 * @property bool $is_serial_tracked
 * @property bool $is_expiry_tracked
 * @property string $default_tax_rate
 * @property int $row_version
 */
class Product extends Model
{
    use SoftDeletes;

    public const TYPES = ['stock', 'non_stock', 'service'];

    protected $fillable = [
        'category_id', 'stock_unit_id', 'sku', 'barcode', 'name', 'description',
        'product_type', 'is_active', 'is_lot_tracked', 'is_serial_tracked',
        'is_expiry_tracked', 'default_tax_rate',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_lot_tracked' => 'boolean',
            'is_serial_tracked' => 'boolean',
            'is_expiry_tracked' => 'boolean',
            'default_tax_rate' => 'decimal:4',
            'deleted_at' => 'datetime',
            'row_version' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'stock_unit_id');
    }
}
