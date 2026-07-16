<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Catalog\Models\Product;
use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only stock-affecting fact. Never updated or deleted; corrections
 * are compensating reversal movements referencing the original.
 *
 * @property int $id
 * @property int $branch_id
 * @property int $product_id
 * @property string $movement_type
 * @property string $quantity_delta
 * @property string $reference_type
 * @property int $reference_id
 */
class InventoryMovement extends Model
{
    public $timestamps = false;

    public const TYPES = ['receipt', 'sale', 'adjustment', 'return', 'reservation', 'release', 'reversal'];

    protected $fillable = [
        'branch_id', 'product_id', 'movement_type', 'quantity_delta', 'on_hand_after_quantity',
        'unit_cost', 'reference_type', 'reference_id', 'reverses_movement_id', 'effective_at',
        'posted_at', 'actor_user_id', 'correlation_id', 'idempotency_key', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_delta' => 'decimal:4',
            'on_hand_after_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'effective_at' => 'datetime',
            'posted_at' => 'datetime',
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
