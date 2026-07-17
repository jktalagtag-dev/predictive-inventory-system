<?php

namespace App\Domains\Governance\Models;

use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Typed, scoped configuration value (DATABASE_DESIGN.md section 10.1).
 * Every write is versioned via row_version and audited by the caller
 * (SettingsService), never edited outside that service.
 *
 * @property int $id
 * @property int|null $branch_id
 * @property string $setting_key
 * @property string $value_type
 * @property mixed $value_json
 * @property bool $is_sensitive
 * @property int $row_version
 */
class Setting extends Model
{
    public const VALUE_TYPES = ['string', 'integer', 'decimal', 'boolean', 'json', 'date'];

    protected $table = 'system_settings';

    protected $fillable = [
        'branch_id', 'setting_key', 'value_type', 'value_json', 'is_sensitive',
        'description', 'row_version', 'created_by_user_id', 'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'value_json' => 'array',
            'is_sensitive' => 'boolean',
            'row_version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
