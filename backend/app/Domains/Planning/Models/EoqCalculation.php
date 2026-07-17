<?php

namespace App\Domains\Planning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An immutable EOQ calculation snapshot. Never overwritten — a new
 * calculation supersedes the previous one but both remain in history
 * (CLAUDE.md section 51, "Storage").
 *
 * @property int $id
 * @property int $reorder_policy_id
 * @property string|null $recommended_order_quantity
 * @property string $status
 */
class EoqCalculation extends Model
{
    public $timestamps = false;

    public const STATUSES = ['valid', 'invalid_input', 'superseded'];

    protected $fillable = [
        'reorder_policy_id', 'annual_demand_quantity', 'ordering_cost', 'annual_holding_cost_per_unit',
        'raw_eoq_quantity', 'recommended_order_quantity', 'currency_code', 'formula_version',
        'input_snapshot', 'status', 'invalid_reason', 'calculated_at', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'annual_demand_quantity' => 'decimal:4',
            'ordering_cost' => 'decimal:4',
            'annual_holding_cost_per_unit' => 'decimal:4',
            'raw_eoq_quantity' => 'decimal:4',
            'recommended_order_quantity' => 'decimal:4',
            'input_snapshot' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    public function reorderPolicy(): BelongsTo
    {
        return $this->belongsTo(ReorderPolicy::class);
    }
}
