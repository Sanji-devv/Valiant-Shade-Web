<?php

namespace App\Models;

use App\Models\Concerns\LogsGuildActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use LogsGuildActivity;
    protected $fillable = [
        'slug',
        'name',
        'guard_name',
        'description',
        'color',
        'icon',
        'priority',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'priority' => 'integer',
        ];
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->mergeFillable(['slug', 'description', 'color', 'icon', 'priority', 'is_system']);
    }

    /**
     * Legacy single-role relationship.
     */
    public function legacyUsers(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'slug');
    }

    public function scopeOrderedByAuthority(Builder $query): Builder
    {
        return $query->orderByDesc('priority')->orderBy('name');
    }

    /**
     * Permission names assigned to this role (Spatie).
     *
     * @return list<string>
     */
    public function permissionNames(): array
    {
        return $this->permissions->pluck('name')->all();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->permissions->contains('name', $permission)) {
            return true;
        }

        if (str_contains($permission, '.')) {
            [$module] = explode('.', $permission, 2);

            return $this->permissions->contains('name', "{$module}.all");
        }

        if (str_ends_with($permission, '.manage')) {
            $module = str_replace('.manage', '', $permission);

            return $this->permissions->contains('name', "{$module}.all");
        }

        return false;
    }
}
