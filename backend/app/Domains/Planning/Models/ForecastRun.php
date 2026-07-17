<?php

namespace App\Domains\Planning\Models;

use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An immutable execution of a forecasting model over a defined scope and
 * cutoff. Never recalculated in place — a changed input produces a new run
 * so prior outputs remain available for comparison and audit
 * (CLAUDE.md section 50, "Historical forecasts").
 *
 * @property int $id
 * @property int|null $branch_id
 * @property string $model_code
 * @property string $status
 */
class ForecastRun extends Model
{
    public $timestamps = false;

    public const STATUSES = ['queued', 'running', 'completed', 'failed'];

    public const PERIOD_GRAINS = ['daily', 'weekly', 'monthly'];

    protected $fillable = [
        'branch_id', 'model_code', 'model_version', 'period_grain', 'window_periods',
        'history_start_date', 'history_end_date', 'data_cutoff_at', 'status',
        'started_at', 'completed_at', 'parameters_snapshot', 'failure_code', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'history_start_date' => 'date',
            'history_end_date' => 'date',
            'data_cutoff_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'parameters_snapshot' => 'array',
            'created_at' => 'datetime',
            'window_periods' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ForecastRunItem::class);
    }
}
