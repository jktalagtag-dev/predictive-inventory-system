<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use App\Domains\Procurement\Models\PurchaseOrderLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $goods_receipt_id
 * @property int|null $purchase_order_line_id
 * @property int $product_id
 * @property string $received_quantity
 * @property string $accepted_quantity
 * @property string $rejected_quantity
 */
class GoodsReceiptLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'goods_receipt_id', 'purchase_order_line_id', 'line_number', 'product_id', 'unit_id',
        'product_sku_snapshot', 'product_name_snapshot', 'received_quantity', 'accepted_quantity',
        'rejected_quantity', 'unit_cost', 'lot_number', 'serial_number', 'expiry_date',
        'rejection_reason', 'notes', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'received_quantity' => 'decimal:4',
            'accepted_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'expiry_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
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
