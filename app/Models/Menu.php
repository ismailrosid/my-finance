<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $table = 'md_menu';

    protected $fillable = [
        'parent_id',
        'menu_code',
        'menu_name',
        'menu_url',
        'menu_icon',
        'menu_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'menu_order' => 'integer',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('menu_order');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'md_role_menu', 'menu_id', 'role_id')
            ->withPivot(['can_view', 'can_create', 'can_update', 'can_delete', 'can_export'])
            ->withTimestamps();
    }

    public function roleMenus(): HasMany
    {
        return $this->hasMany(RoleMenu::class, 'menu_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeParent($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('menu_order');
    }
}

