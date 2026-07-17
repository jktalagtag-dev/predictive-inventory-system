<?php

namespace App\Domains\Planning\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Current lifecycle of one deduplicated replenishment alert at
 * reorder-policy grain. Alert generation never auto-purchases stock — it
 * only surfaces a recommendation for a user to act on (CLAUDE.md
 * section 53).
 *
 * @property int $id
 * @property int $reorder_policy_id
 * @property string $status
 * @property string $severity
 * @property int $row_version
 */
class RestockingAlert extends Model
{
    public const STATUSES = ['active', 'acknowledged', 'resolved', 'dismissed'];

    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];

    protected $fillable = [
        'reorder_policy_id', 'status', 'severity', 'available_quantity_snapshot',
        'incoming_quantity_snapshot', 'reorder_point_snapshot', 'recommended_order_quantity',
        'first_triggered_at', 'last_evaluated_at', 'resolved_at', 'dismissal_reason',
        'assigned_to_user_id', 'row_version', 'created_by_user_id', 'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'available_quantity_snapshot' => 'decimal:4',
            'incoming_quantity_snapshot' => 'decimal:4',
            'reorder_point_snapshot' => 'decimal:4',
            'recommended_order_quantity' => 'decimal:4',
            'first_triggered_at' => 'datetime',
            'last_evaluated_at' => 'datetime',
            'resolved_at' => 'datetime',
            'row_version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function reorderPolicy(): BelongsTo
    {
        return $this->belongsTo(ReorderPolicy::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RestockingAlertEvent::class);
    }
}
