<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'avatar_url',
        'locale',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // Relationships
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->withTimestamps();
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'user_id');
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class, 'user_id');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class, 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    // Role helpers
    public function hasRole(string $roleCode): bool
    {
        return $this->roles()->where('role_code', $roleCode)->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->isSuperAdmin();
    }

    public function hasPermission(string $menuCode, string $permission = 'can_view'): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roles()
            ->join('md_role_menu', 'md_role.id', '=', 'md_role_menu.role_id')
            ->join('md_menu', 'md_menu.id', '=', 'md_role_menu.menu_id')
            ->where('md_menu.menu_code', $menuCode)
            ->where('md_role_menu.' . $permission, true)
            ->exists();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function getAvatarUrlAttribute($value): string
    {
        return $value ?: asset('images/default-avatar.png');
    }

    public function getLocale(): string
    {
        return in_array($this->locale, ['en', 'id']) ? $this->locale : 'en';
    }
}
