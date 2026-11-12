<?php

namespace App\ValiantShade\Services;

use App\Models\PermissionRoute;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RoleService
{
    public function __construct(
        protected PermissionRouteRegistry $routeRegistry,
        protected PermissionSyncService $permissionSync,
    ) {}

    public function getPermissionCatalog(): array
    {
        return Cache::remember('valiant_shade.permissions.catalog', 3600, function () {
            $this->permissionSync->syncFromRoutesTable();

            $catalog = [];
            $actionLabels = [
                'read' => __('Read'),
                'create' => __('Create'),
                'update' => __('Update'),
                'delete' => __('Delete'),
                'all' => __('Full access (all actions)'),
            ];

            $routes = PermissionRoute::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            foreach ($routes as $route) {
                $actions = [];
                foreach (PermissionRouteRegistry::ACTIONS as $action) {
                    $key = "{$route->key}.{$action}";
                    $actions[$action] = [
                        'key' => $key,
                        'label' => $actionLabels[$action],
                    ];
                }

                $catalog[$route->key] = [
                    'key' => $route->key,
                    'name' => __($route->name),
                    'description' => __($route->description ?? ''),
                    'icon' => $route->icon,
                    'badge' => $route->badge,
                    'actions' => $actions,
                ];
            }

            return $catalog;
        });
    }

    /**
     * Flat permission list for matrices and checkboxes (module.action => meta).
     */
    public function getFlatPermissionCatalog(): array
    {
        $flat = [];
        foreach ($this->getPermissionCatalog() as $module) {
            foreach ($module['actions'] as $action) {
                $flat[$action['key']] = [
                    'key' => $action['key'],
                    'name' => $module['name'] . ' — ' . $action['label'],
                    'module' => $module['key'],
                    'action' => str_replace($module['key'] . '.', '', $action['key']),
                    'description' => $module['description'],
                    'icon' => $module['icon'],
                    'badge' => $module['badge'],
                ];
            }
        }

        return $flat;
    }

    public function getDefaultRoles(): array
    {
        $allModules = array_column($this->routeRegistry->definitions(), 'key');
        $allPermissions = array_map(fn ($m) => "{$m}.all", $allModules);

        return [
            [
                'slug' => 'ADMIN',
                'name' => 'Administrator',
                'description' => 'Highest executive and administrative authority in the guild.',
                'color' => '#ea5455',
                'icon' => 'ti tabler-crown',
                'priority' => 100,
                'permissions' => $allPermissions,
                'is_system' => true,
            ],
            [
                'slug' => 'GM',
                'name' => 'Game Master',
                'description' => 'Oversees expeditions, dungeon campaigns, and chronicles.',
                'color' => '#7367f0',
                'icon' => 'ti tabler-wand',
                'priority' => 80,
                'permissions' => ['quests.all', 'xp.all', 'guild.all', 'archives.all'],
                'is_system' => true,
            ],
            [
                'slug' => 'BAILIFF',
                'name' => 'High Bailiff',
                'description' => 'Overseer of the Bailiff Council and guild administration.',
                'color' => '#ff9f43',
                'icon' => 'ti tabler-shield-check',
                'priority' => 70,
                'permissions' => ['guild.all', 'discipline.all', 'hr.all', 'inventory.all', 'quests.all', 'archives.all', 'diplomacy.all', 'oaths.all'],
                'is_system' => true,
            ],
            [
                'slug' => 'BAILIFF_DISCIPLINE',
                'name' => 'Discipline Bailiff',
                'description' => 'Oversees guild discipline, rule compliance, sanctions, and member conduct.',
                'color' => '#e65100',
                'icon' => 'ti tabler-gavel',
                'priority' => 65,
                'permissions' => ['discipline.all', 'users.all', 'guild.read'],
                'is_system' => true,
            ],
            [
                'slug' => 'BAILIFF_HERO_RESOURCES',
                'name' => 'Hero Resources Bailiff',
                'description' => 'Manages adventurer intake, rank advancement, roster assignments, and progression.',
                'color' => '#0288d1',
                'icon' => 'ti tabler-users-group',
                'priority' => 65,
                'permissions' => ['hr.all', 'xp.all', 'users.all', 'guild.read'],
                'is_system' => true,
            ],
            [
                'slug' => 'BAILIFF_REPRESENTATIVE',
                'name' => 'Guild Representative Bailiff',
                'description' => 'Oversees external diplomacy, guild advocacy, faction treaties, and public representation.',
                'color' => '#20c997',
                'icon' => 'ti tabler-flag',
                'priority' => 65,
                'permissions' => ['diplomacy.all', 'guild.read', 'users.read'],
                'is_system' => true,
            ],
            [
                'slug' => 'BAILIFF_ARCHIVIST',
                'name' => 'Archivist Bailiff',
                'description' => 'Curates guild chronicles, expedition debriefs, lore repositories, and historical scrolls.',
                'color' => '#6f42c1',
                'icon' => 'ti tabler-books',
                'priority' => 65,
                'permissions' => ['archives.all', 'quests.all', 'guild.read'],
                'is_system' => true,
            ],
            [
                'slug' => 'BAILIFF_OATHKEEPER',
                'name' => 'Oathkeeper Bailiff',
                'description' => 'Enforces sworn guild covenants, adventurer pledges of allegiance, and sacred vows.',
                'color' => '#d63384',
                'icon' => 'ti tabler-shield-lock',
                'priority' => 65,
                'permissions' => ['oaths.all', 'discipline.all', 'guild.read'],
                'is_system' => true,
            ],
            [
                'slug' => 'BAILIFF_INVENTORY',
                'name' => 'Inventory Bailiff',
                'description' => 'Manages the guild communal vault, magic artifacts, and treasury assets.',
                'color' => '#f57c00',
                'icon' => 'ti tabler-package',
                'priority' => 60,
                'permissions' => ['inventory.all', 'guild.read'],
                'is_system' => true,
            ],
            [
                'slug' => 'BAILIFF_QUESTMASTER',
                'name' => 'Questmaster Bailiff',
                'description' => 'Supervises quest board notices, decrees, and expedition debriefs.',
                'color' => '#7b1fa2',
                'icon' => 'ti tabler-clipboard-list',
                'priority' => 60,
                'permissions' => ['quests.all', 'guild.read'],
                'is_system' => true,
            ],
            [
                'slug' => 'VETERAN',
                'name' => 'Veteran Adventurer',
                'description' => 'Hardened veterans who survived high-stakes guild expeditions.',
                'color' => '#00cfe8',
                'icon' => 'ti tabler-swords',
                'priority' => 40,
                'permissions' => [],
                'is_system' => false,
            ],
            [
                'slug' => 'MEMBER',
                'name' => 'Guild Member',
                'description' => 'Fully initiated and sworn member of Valiant Shade.',
                'color' => '#28c76f',
                'icon' => 'ti tabler-medal',
                'priority' => 20,
                'permissions' => [],
                'is_system' => false,
            ],
            [
                'slug' => 'PLAYER',
                'name' => 'Adventurer',
                'description' => 'Standard guild adventurer profile.',
                'color' => '#82868b',
                'icon' => 'ti tabler-user',
                'priority' => 10,
                'permissions' => [],
                'is_system' => true,
            ],
        ];
    }

    public function clearCache(): void
    {
        Cache::forget('valiant_shade.roles.all');
        Cache::forget('valiant_shade.permissions.catalog');
    }

    public function getAllRoles(): Collection
    {
        $cached = Cache::get('valiant_shade.roles.all');
        if ($cached instanceof Collection) {
            return $cached;
        }

        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get()
            ->map(fn (Role $role) => $this->formatRole($role));

        Cache::put('valiant_shade.roles.all', $roles, 3600);

        return $roles;
    }

    public function findRole(int|string $identifier): ?array
    {
        $role = $this->findRoleModel($identifier);

        return $role ? $this->formatRole($role) : null;
    }

    public function findRoleModel(int|string $identifier): ?Role
    {
        return Role::query()
            ->with('permissions')
            ->withCount('users')
            ->when(is_numeric($identifier), fn ($q) => $q->where('id', $identifier))
            ->when(! is_numeric($identifier), fn ($q) => $q->where('slug', strtoupper((string) $identifier)))
            ->first();
    }

    public function createRole(array $data): array
    {
        $slug = strtoupper(Str::slug($data['name'], '_'));
        if (empty($slug)) {
            $slug = 'ROLE_' . Str::upper(Str::random(6));
        }

        if (Role::where('slug', $slug)->exists()) {
            throw new \InvalidArgumentException(__('A role with this name or slug already exists.'));
        }

        $role = Role::create([
            'slug' => $slug,
            'name' => $data['name'],
            'guard_name' => 'web',
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? '#7367f0',
            'icon' => $data['icon'] ?? 'ti tabler-shield',
            'priority' => (int) ($data['priority'] ?? 10),
            'is_system' => false,
        ]);

        $this->syncRolePermissions($role, $data['permissions'] ?? []);
        $this->clearCache();

        return $this->formatRole($role->fresh(['permissions'])->loadCount('users'));
    }

    public function updateRole(int|string $identifier, array $data): array
    {
        $role = $this->findRoleModel($identifier);
        if (! $role) {
            throw new \InvalidArgumentException(__('Role not found.'));
        }

        $permissions = $data['permissions'] ?? null;
        if ($role->slug === 'ADMIN' && is_array($permissions)) {
            if (! in_array('roles.all', $permissions, true)) {
                $permissions[] = 'roles.all';
            }
            $data['permissions'] = array_values(array_unique($permissions));
        }

        $role->update([
            'name' => $data['name'] ?? $role->name,
            'description' => $data['description'] ?? $role->description,
            'color' => $data['color'] ?? $role->color,
            'icon' => $data['icon'] ?? $role->icon,
            'priority' => isset($data['priority']) ? (int) $data['priority'] : $role->priority,
        ]);

        if (array_key_exists('permissions', $data)) {
            $this->syncRolePermissions($role, $data['permissions'] ?? []);
        }

        $this->clearCache();

        return $this->formatRole($role->fresh(['permissions'])->loadCount('users'));
    }

    public function togglePermission(int|string $identifier, string $permission, bool $enabled): array
    {
        $role = $this->findRoleModel($identifier);
        if (! $role) {
            throw new \InvalidArgumentException(__('Role not found.'));
        }

        if ($role->slug === 'ADMIN' && ! $enabled && in_array($permission, ['roles.all', 'roles.manage'], true)) {
            throw new \InvalidArgumentException(__('The Administrator role must retain role management authority.'));
        }

        $permission = $this->normalizePermissionKey($permission);

        if ($enabled) {
            $role->givePermissionTo($permission);
        } else {
            $role->revokePermissionTo($permission);
        }

        activity('permissions')
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->event('permission.toggled')
            ->withProperties([
                'permission' => $permission,
                'enabled' => $enabled,
                'role_slug' => $role->slug,
                'ip' => request()?->ip(),
            ])
            ->log($enabled
                ? __('Granted permission ":perm" for role ":name".', ['perm' => $permission, 'name' => $role->name])
                : __('Revoked permission ":perm" for role ":name".', ['perm' => $permission, 'name' => $role->name]));

        $this->clearCache();

        return $this->formatRole($role->fresh(['permissions'])->loadCount('users'));
    }

    public function deleteRole(int|string $identifier): bool
    {
        $role = $this->findRoleModel($identifier);
        if (! $role) {
            return false;
        }

        if ($role->is_system) {
            throw new \InvalidArgumentException(__('Built-in system roles cannot be deleted.'));
        }

        if ($role->users()->count() > 0) {
            throw new \InvalidArgumentException(__('You cannot delete a role that still has adventurers assigned to it. Reassign users first.'));
        }

        $role->delete();
        $this->clearCache();

        return true;
    }

    public function syncUserDirectPermissions(\App\Models\User $user, array $permissions): void
    {
        $normalized = array_map(fn ($p) => $this->normalizePermissionKey($p), $permissions);
        $user->syncPermissions($normalized);

        activity('permissions')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('permission.user_sync')
            ->withProperties([
                'permissions' => $normalized,
                'user_id' => $user->id,
                'ip' => request()?->ip(),
            ])
            ->log(__('Synchronized direct permissions for user ":name".', ['name' => $user->display_name ?? $user->name]));
    }

    protected function syncRolePermissions(Role $role, array $permissions): void
    {
        $normalized = array_values(array_unique(array_map(
            fn ($p) => $this->normalizePermissionKey((string) $p),
            $permissions
        )));

        $role->syncPermissions($normalized);

        activity('permissions')
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->event('permission.sync')
            ->withProperties([
                'permissions' => $normalized,
                'role_slug' => $role->slug,
                'ip' => request()?->ip(),
            ])
            ->log(__('Synchronized permissions for role ":name".', ['name' => $role->name]));
    }

    protected function normalizePermissionKey(string $permission): string
    {
        if (str_ends_with($permission, '.manage')) {
            return str_replace('.manage', '.all', $permission);
        }

        return $permission;
    }

    protected function formatRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'slug' => $role->slug,
            'name' => $role->name,
            'description' => $role->description,
            'color' => $role->color,
            'icon' => $role->icon,
            'priority' => $role->priority,
            'is_system' => (bool) $role->is_system,
            'users_count' => (int) ($role->users_count ?? $role->users()->count()),
            'permissions' => $role->permissionNames(),
        ];
    }
}
