<?php

namespace App\Domains\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One payment allocation for a completed sale. Never stores raw payment
 * credentials — only a controlled method and an optional external
 * processor reference, per CLAUDE.md section 43/56.
 *
 * @property int $id
 * @property int $sale_id
 * @property string $payment_method
 * @property string $amount
 */
class SalePayment extends Model
{
    public $timestamps = false;

    public const METHODS = ['cash', 'card', 'bank_transfer', 'ewallet', 'other'];

    protected $fillable = [
        'sale_id', 'payment_method', 'amount', 'currency_code', 'external_reference', 'received_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'received_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
