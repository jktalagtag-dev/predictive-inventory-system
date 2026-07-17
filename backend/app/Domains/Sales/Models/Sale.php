<?php

namespace App\Domains\Sales\Models;

use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A completed POS business document, not a mutable cart — client cart
 * drafts stay outside this table until finalization. Completed sales are
 * never edited; void() and refund() on SaleService append compensating
 * inventory movements and flip status, per CLAUDE.md section 43/49.
 *
 * @property int $id
 * @property int $branch_id
 * @property string $sale_number
 * @property string $status
 * @property int $cashier_user_id
 * @property string $idempotency_key
 */
class Sale extends Model
{
    public $timestamps = false;

    public const STATUSES = ['completed', 'voided', 'refunded'];

    protected $fillable = [
        'branch_id', 'sale_number', 'status', 'currency_code', 'sold_at', 'completed_at',
        'voided_at', 'refunded_at', 'reverses_sale_id', 'subtotal_amount', 'discount_amount',
        'tax_amount', 'total_amount', 'cashier_user_id', 'approved_by_user_id',
        'idempotency_key', 'correlation_id', 'notes', 'row_version',
    ];

    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'completed_at' => 'datetime',
            'voided_at' => 'datetime',
            'refunded_at' => 'datetime',
            'subtotal_amount' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'row_version' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function reversesSale(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_sale_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reverses_sale_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }
}
