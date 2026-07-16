<?php

namespace App\Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_system_role
 */
class Role extends Model
{
    protected $fillable = ['code', 'name', 'description', 'is_system_role'];

    protected function casts(): array
    {
        return [
            'is_system_role' => 'boolean',
            'row_version' => 'integer',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot(['granted_by_user_id']);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot(['effective_from', 'effective_until', 'granted_by_user_id']);
    }
}
