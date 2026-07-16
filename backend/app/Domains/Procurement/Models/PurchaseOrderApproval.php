<?php

namespace App\Domains\Procurement\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $approval_stage
 * @property string $decision
 * @property int $decision_by_user_id
 */
class PurchaseOrderApproval extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'purchase_order_id', 'approval_stage', 'decision', 'decision_by_user_id',
        'decision_at', 'reason', 'policy_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'decision_at' => 'datetime',
            'policy_snapshot' => 'array',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function decisionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by_user_id');
    }
}
