<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['slug', 'name', 'permissions'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
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
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'slug');
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }
}