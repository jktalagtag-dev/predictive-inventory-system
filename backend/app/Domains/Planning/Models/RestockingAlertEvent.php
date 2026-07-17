<?php

namespace App\Domains\Planning\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only alert lifecycle history. Never updated or deleted.
 *
 * @property int $id
 * @property int $restocking_alert_id
 * @property string $event_type
 */
class RestockingAlertEvent extends Model
{
    public $timestamps = false;

    public const EVENT_TYPES = ['triggered', 're_evaluated', 'acknowledged', 'resolved', 'dismissed'];

    protected $fillable = [
        'restocking_alert_id', 'event_type', 'from_status', 'to_status',
        'details', 'actor_user_id', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(RestockingAlert::class, 'restocking_alert_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
