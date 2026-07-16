<?php

namespace App\Domains\Procurement\Models;

use App\Domains\Identity\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $supplier_id
 * @property string $po_number
 * @property string $status
 * @property int $row_version
 */
class PurchaseOrder extends Model
{
    public const STATUSES = ['draft', 'submitted', 'approved', 'ordered', 'partially_received', 'received', 'cancelled', 'closed'];

    protected $fillable = [
        'branch_id', 'supplier_id', 'po_number', 'status', 'currency_code',
        'ordered_at', 'expected_receipt_at', 'submitted_at', 'approved_at', 'cancelled_at',
        'subtotal_amount', 'tax_amount', 'discount_amount', 'total_amount',
        'supplier_reference', 'notes', 'row_version', 'created_by_user_id', 'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'expected_receipt_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'subtotal_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'row_version' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PurchaseOrderApproval::class);
    }
}
