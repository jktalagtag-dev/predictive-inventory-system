<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\User;
use App\Domains\Procurement\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $purchase_order_id
 * @property int $branch_id
 * @property string $receipt_number
 * @property string $status
 * @property int|null $created_by_user_id
 * @property int $row_version
 */
class GoodsReceipt extends Model
{
    public const STATUSES = ['draft', 'posted', 'reversed'];

    protected $fillable = [
        'purchase_order_id', 'branch_id', 'receipt_number', 'status', 'supplier_delivery_number',
        'received_at', 'posted_at', 'reversed_at', 'reversal_receipt_id', 'notes',
        'row_version', 'created_by_user_id', 'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
            'row_version' => 'integer',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reversalReceipt(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_receipt_id');
    }
}
