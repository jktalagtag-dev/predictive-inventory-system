<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $adjustment_number
 * @property string $status
 * @property string $reason_code
 * @property int|null $created_by_user_id
 * @property int|null $approved_by_user_id
 * @property int $row_version
 */
class InventoryAdjustment extends Model
{
    public const STATUSES = ['draft', 'pending_approval', 'posted', 'rejected', 'reversed'];

    protected $fillable = [
        'branch_id', 'adjustment_number', 'status', 'reason_code', 'reason_note',
        'effective_at', 'approved_by_user_id', 'approved_at', 'posted_at',
        'reversal_adjustment_id', 'row_version', 'created_by_user_id', 'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'effective_at' => 'datetime',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'row_version' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryAdjustmentLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function reversalAdjustment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_adjustment_id');
    }
}
