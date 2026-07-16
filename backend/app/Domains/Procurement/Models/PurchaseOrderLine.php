<?php

namespace App\Domains\Procurement\Models;

use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $product_id
 * @property string $ordered_quantity
 * @property string $received_quantity
 */
class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'purchase_order_id', 'line_number', 'product_id', 'unit_id',
        'product_sku_snapshot', 'product_name_snapshot', 'ordered_quantity', 'received_quantity',
        'unit_cost', 'tax_rate', 'discount_amount', 'net_amount', 'tax_amount', 'total_amount',
        'expected_receipt_at', 'notes', 'created_by_user_id', 'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'ordered_quantity' => 'decimal:4',
            'received_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'net_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'expected_receipt_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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
