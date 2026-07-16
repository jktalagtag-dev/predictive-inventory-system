<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Catalog\Models\Product;
use App\Domains\Identity\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-model projection of current stock per product-branch. Never the
 * source of historical truth — inventory_movements is authoritative and
 * this projection must remain reproducible from it.
 *
 * @property int $id
 * @property int $branch_id
 * @property int $product_id
 * @property string $on_hand_quantity
 * @property string $reserved_quantity
 * @property string $available_quantity
 * @property string $incoming_quantity
 * @property int $row_version
 */
class InventoryBalance extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'branch_id', 'product_id', 'on_hand_quantity', 'reserved_quantity',
        'available_quantity', 'incoming_quantity', 'last_movement_at', 'row_version',
    ];

    protected function casts(): array
    {
        return [
            'on_hand_quantity' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
            'available_quantity' => 'decimal:4',
            'incoming_quantity' => 'decimal:4',
            'last_movement_at' => 'datetime',
            'row_version' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
