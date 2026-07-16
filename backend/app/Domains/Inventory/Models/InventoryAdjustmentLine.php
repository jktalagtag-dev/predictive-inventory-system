<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $inventory_adjustment_id
 * @property int $product_id
 * @property string $quantity_delta
 */
class InventoryAdjustmentLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'inventory_adjustment_id', 'line_number', 'product_id', 'product_sku_snapshot',
        'product_name_snapshot', 'before_quantity', 'quantity_delta', 'after_quantity',
        'unit_cost', 'cost_impact_amount', 'notes', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'before_quantity' => 'decimal:4',
            'quantity_delta' => 'decimal:4',
            'after_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'cost_impact_amount' => 'decimal:4',
        ];
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
