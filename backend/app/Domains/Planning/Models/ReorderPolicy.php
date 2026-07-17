<?php

namespace App\Domains\Planning\Models;

use App\Domains\Catalog\Models\Product;
use App\Domains\Identity\Models\Branch;
use App\Domains\Procurement\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product-branch replenishment policy. The reorder point itself is always
 * derived (never directly edited) — only safety stock, lead time source,
 * and preferred supplier are mutable inputs (CLAUDE.md section 52).
 *
 * @property int $id
 * @property int $branch_id
 * @property int $product_id
 * @property string $safety_stock_quantity
 * @property string|null $lead_time_days_override
 * @property string|null $reorder_point_quantity
 * @property int $row_version
 */
class ReorderPolicy extends Model
{
    public const SAFETY_STOCK_BASES = ['policy_minimum', 'service_level', 'manual_override'];

    public const LEAD_TIME_BASES = ['supplier', 'product_default', 'override'];

    protected $fillable = [
        'branch_id', 'product_id', 'preferred_supplier_id', 'safety_stock_quantity',
        'safety_stock_basis', 'lead_time_days_override', 'lead_time_basis',
        'reorder_point_quantity', 'rop_calculated_at', 'is_active', 'row_version',
        'created_by_user_id', 'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'safety_stock_quantity' => 'decimal:4',
            'lead_time_days_override' => 'decimal:2',
            'reorder_point_quantity' => 'decimal:4',
            'rop_calculated_at' => 'datetime',
            'is_active' => 'boolean',
            'row_version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }

    public function eoqCalculations(): HasMany
    {
        return $this->hasMany(EoqCalculation::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(RestockingAlert::class);
    }
}
