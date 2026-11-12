<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'md_role';

    protected $fillable = [
        'role_code',
        'role_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'md_role_menu', 'role_id', 'menu_id')
            ->withPivot(['can_view', 'can_create', 'can_update', 'can_delete', 'can_export'])
            ->withTimestamps();
    }

    public function roleMenus(): HasMany
    {
        return $this->hasMany(RoleMenu::class, 'role_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

