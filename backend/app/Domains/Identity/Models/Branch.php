<?php

namespace App\Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_active
 * @property int $row_version
 */
class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'address_line_1', 'address_line_2', 'city',
        'province', 'postal_code', 'country_code', 'phone', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
            'row_version' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branches')
            ->withPivot(['is_default', 'granted_by_user_id']);
    }
}
