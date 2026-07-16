<?php

namespace App\Domains\Identity\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $email
 * @property string $password_hash
 * @property string $first_name
 * @property string $last_name
 * @property string $display_name
 * @property bool $is_active
 * @property int $row_version
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'display_name',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'mfa_enabled_at' => 'datetime',
            'password_hash' => 'hashed',
            'is_active' => 'boolean',
            'row_version' => 'integer',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['effective_from', 'effective_until', 'granted_by_user_id']);
    }

    public function activeRoles()
    {
        return $this->roles()
            ->wherePivot('effective_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('user_roles.effective_until')
                    ->orWhere('user_roles.effective_until', '>', now());
            });
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branches')
            ->withPivot(['is_default', 'granted_by_user_id']);
    }

    public function hasPermission(string $permissionCode): bool
    {
        return $this->activeRoles()
            ->whereHas('permissions', fn ($query) => $query->where('code', $permissionCode))
            ->exists();
    }

    public function hasRole(string $roleCode): bool
    {
        return $this->activeRoles()->where('code', $roleCode)->exists();
    }

    /**
     * Owners have organization-wide visibility; every other role is
     * restricted to explicitly assigned branches.
     */
    public function canAccessBranch(int $branchId): bool
    {
        if ($this->hasRole('owner')) {
            return true;
        }

        return $this->branches()->where('branches.id', $branchId)->exists();
    }
}
