<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'color',
        'icon',
        'priority',
        'permissions',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
            'priority' => 'integer',
        ];
    }

    public static function permissionCatalog(): array
    {
        return [
            'users.manage' => 'Manage users',
            'roles.manage' => 'Manage roles and permissions',
            'guild.manage' => 'Manage guild overview and bailiffs',
            'inventory.manage' => 'Manage inventory and guild vault',
            'xp.manage' => 'Manage player experience',
            'quests.manage' => 'Manage quest board',
            'discipline.manage' => 'Manage guild discipline and sanctions',
            'hr.manage' => 'Manage hero resources and rank promotions',
            'archives.manage' => 'Manage guild archives and chronicles',
            'diplomacy.manage' => 'Manage guild representation and diplomacy',
            'oaths.manage' => 'Manage sacred oaths and covenant compliance',
        ];
    }

    /**
     * Many-to-many relationship with users (Discord-style multi-roles).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Legacy single-role relationship.
     */
    public function legacyUsers(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'slug');
    }

    /**
     * Scope to order roles by authority (highest authority first).
     */
    public function scopeOrderedByAuthority(Builder $query): Builder
    {
        return $query->orderByDesc('priority')->orderBy('name');
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }
}