<?php

namespace App\Domains\Sales\Models;

use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sale_id
 * @property int $product_id
 * @property string $quantity
 * @property string $stock_quantity_delta
 * @property string $unit_price
 */
class SaleLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id', 'line_number', 'product_id', 'unit_id', 'product_sku_snapshot',
        'product_name_snapshot', 'quantity', 'stock_quantity_delta', 'unit_price',
        'discount_amount', 'tax_rate', 'tax_amount', 'line_total_amount', 'override_reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'stock_quantity_delta' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'line_total_amount' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }
}
