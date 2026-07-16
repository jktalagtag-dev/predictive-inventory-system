<?php

namespace App\Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $module
 */
class Permission extends Model
{
    protected $fillable = ['code', 'name', 'module', 'description'];

    protected function casts(): array
    {
        return ['row_version' => 'integer'];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withPivot(['granted_by_user_id']);
    }
}
