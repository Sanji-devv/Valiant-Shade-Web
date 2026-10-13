<?php

namespace App\ValiantShade\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleService
{
    protected function getStoragePath(): string
    {
        return storage_path('app/roles.json');
    }

    public function getPermissionCatalog(): array
    {
        return Cache::remember('valiant_shade.permissions.catalog', 86400, function () {
            return [
            'users.manage' => [
                'key' => 'users.manage',
                'name' => __('User Management'),
                'description' => __('List users, create accounts, delete accounts, and sync characters.'),
                'icon' => 'ti tabler-users',
                'badge' => 'bg-label-primary',
            ],
            'roles.manage' => [
                'key' => 'roles.manage',
                'name' => __('Roles & Permissions'),
                'description' => __('Create roles, edit roles, and configure permission access.'),
                'icon' => 'ti tabler-shield-lock',
                'badge' => 'bg-label-danger',
            ],
            'guild.manage' => [
                'key' => 'guild.manage',
                'name' => __('Guild Governance'),
                'description' => __('Manage guild level, bailiff council officers, and demographics.'),
                'icon' => 'ti tabler-building-castle',
                'badge' => 'bg-label-warning',
            ],
            'inventory.manage' => [
                'key' => 'inventory.manage',
                'name' => __('Inventory & Vault'),
                'description' => __('Manage guild vault reserves, items, and adventurer gear assignments.'),
                'icon' => 'ti tabler-package',
                'badge' => 'bg-label-info',
            ],
            'xp.manage' => [
                'key' => 'xp.manage',
                'name' => __('Adventurer XP Tracking'),
                'description' => __('Award quick XP, advance adventurer ranks, and manage level thresholds.'),
                'icon' => 'ti tabler-award',
                'badge' => 'bg-label-success',
            ],
            'discipline.manage' => [
                'key' => 'discipline.manage',
                'name' => __('Discipline & Conduct'),
                'description' => __('Manage guild discipline, infractions, warnings, and code of conduct enforcement.'),
                'icon' => 'ti tabler-gavel',
                'badge' => 'bg-label-danger',
            ],
            'hr.manage' => [
                'key' => 'hr.manage',
                'name' => __('Hero Resources & Roster'),
                'description' => __('Manage adventurer onboarding, rank advancement approvals, and hero personnel records.'),
                'icon' => 'ti tabler-users-group',
                'badge' => 'bg-label-info',
            ],
            'quests.manage' => [
                'key' => 'quests.manage',
                'name' => __('Quest Board & Decrees'),
                'description' => __('Pin quest notices, edit decrees, update status, and broadcast to Discord.'),
                'icon' => 'ti tabler-scroll',
                'badge' => 'bg-label-secondary',
            ],
            'archives.manage' => [
                'key' => 'archives.manage',
                'name' => __('Archives & Chronicles'),
                'description' => __('Manage guild archives, chronicles, history, and official scrolls.'),
                'icon' => 'ti tabler-books',
                'badge' => 'bg-label-primary',
            ],
            'diplomacy.manage' => [
                'key' => 'diplomacy.manage',
                'name' => __('Guild Representation & Diplomacy'),
                'description' => __('Represent the guild in external affairs, pacts, and faction diplomacy.'),
                'icon' => 'ti tabler-flag',
                'badge' => 'bg-label-success',
            ],
            'oaths.manage' => [
                'key' => 'oaths.manage',
                'name' => __('Oaths & Covenant Enforcement'),
                'description' => __('Oversee sworn oaths, membership vows, and code of honor fidelity.'),
                'icon' => 'ti tabler-shield-lock',
            ],
        ];
    });
}

    /**
     * Default role templates.
     */
    public function getDefaultRoles(): array
    {
        $allPermissions = array_keys($this->getPermissionCatalog());

        return [
            [
                'id' => 1,
                'slug' => 'ADMIN',
                'name' => 'Administrator',
                'description' => 'Highest executive and administrative authority in the guild.',
                'color' => '#ea5455',
                'icon' => 'ti tabler-crown',
                'priority' => 100,
                'permissions' => $allPermissions,
                'is_system' => true,
                'is_bailiff' => false,
                'users_count' => 1,
            ],
            [
                'id' => 2,
                'slug' => 'GM',
                'name' => 'Game Master',
                'description' => 'Oversees expeditions, dungeon campaigns, and chronicles.',
                'color' => '#7367f0',
                'icon' => 'ti tabler-wand',
                'priority' => 80,
                'permissions' => ['quests.manage', 'xp.manage', 'guild.manage', 'archives.manage'],
                'is_system' => true,
                'is_bailiff' => false,
                'users_count' => 2,
            ],
            [
                'id' => 3,
                'slug' => 'BAILIFF',
                'name' => 'High Bailiff',
                'description' => 'Overseer of the Bailiff Council and guild administration.',
                'color' => '#ff9f43',
                'icon' => 'ti tabler-shield-check',
                'priority' => 70,
                'permissions' => [
                    'guild.manage',
                    'discipline.manage',
                    'hr.manage',
                    'inventory.manage',
                    'quests.manage',
                    'archives.manage',
                    'diplomacy.manage',
                    'oaths.manage',
                ],
                'is_system' => true,
                'is_bailiff' => true,
                'bailiff_tier' => 'Council President',
                'users_count' => 1,
            ],
            [
                'id' => 4,
                'slug' => 'BAILIFF_DISCIPLINE',
                'name' => 'Discipline Bailiff',
                'description' => 'Oversees guild discipline, rule compliance, sanctions, and member conduct.',
                'color' => '#e65100',
                'icon' => 'ti tabler-gavel',
                'priority' => 65,
                'permissions' => ['discipline.manage', 'users.manage', 'guild.manage'],
                'is_system' => true,
                'is_bailiff' => true,
                'bailiff_tier' => 'Discipline & Conduct',
                'users_count' => 1,
            ],
            [
                'id' => 5,
                'slug' => 'BAILIFF_HERO_RESOURCES',
                'name' => 'Hero Resources Bailiff',
                'description' => 'Manages adventurer intake, rank advancement, roster assignments, and progression.',
                'color' => '#0288d1',
                'icon' => 'ti tabler-users-group',
                'priority' => 65,
                'permissions' => ['hr.manage', 'xp.manage', 'users.manage', 'guild.manage'],
                'is_system' => true,
                'is_bailiff' => true,
                'bailiff_tier' => 'Hero Resources & Roster',
                'users_count' => 1,
            ],
            [
                'id' => 6,
                'slug' => 'BAILIFF_REPRESENTATIVE',
                'name' => 'Guild Representative Bailiff',
                'description' => 'Oversees external diplomacy, guild advocacy, faction treaties, and public representation.',
                'color' => '#20c997',
                'icon' => 'ti tabler-flag',
                'priority' => 65,
                'permissions' => ['diplomacy.manage', 'guild.manage', 'users.manage'],
                'is_system' => true,
                'is_bailiff' => true,
                'bailiff_tier' => 'Diplomacy & Representation',
                'users_count' => 1,
            ],
            [
                'id' => 7,
                'slug' => 'BAILIFF_ARCHIVIST',
                'name' => 'Archivist Bailiff',
                'description' => 'Curates guild chronicles, expedition debriefs, lore repositories, and historical scrolls.',
                'color' => '#6f42c1',
                'icon' => 'ti tabler-books',
                'priority' => 65,
                'permissions' => ['archives.manage', 'quests.manage', 'guild.manage'],
                'is_system' => true,
                'is_bailiff' => true,
                'bailiff_tier' => 'Archives & Chronicles',
                'users_count' => 1,
            ],
            [
                'id' => 8,
                'slug' => 'BAILIFF_OATHKEEPER',
                'name' => 'Oathkeeper Bailiff',
                'description' => 'Enforces sworn guild covenants, adventurer pledges of allegiance, and sacred vows.',
                'color' => '#d63384',
                'icon' => 'ti tabler-shield-lock',
                'priority' => 65,
                'permissions' => ['oaths.manage', 'discipline.manage', 'guild.manage'],
                'is_system' => true,
                'is_bailiff' => true,
                'bailiff_tier' => 'Sacred Oaths & Covenants',
                'users_count' => 1,
            ],
            [
                'id' => 9,
                'slug' => 'BAILIFF_INVENTORY',
                'name' => 'Inventory Bailiff',
                'description' => 'Manages the guild communal vault, magic artifacts, and treasury assets.',
                'color' => '#f57c00',
                'icon' => 'ti tabler-package',
                'priority' => 60,
                'permissions' => ['inventory.manage', 'guild.manage'],
                'is_system' => true,
                'is_bailiff' => true,
                'bailiff_tier' => 'Vault & Treasury',
                'users_count' => 1,
            ],
            [
                'id' => 10,
                'slug' => 'BAILIFF_QUESTMASTER',
                'name' => 'Questmaster Bailiff',
                'description' => 'Supervises quest board notices, decrees, and expedition debriefs.',
                'color' => '#7b1fa2',
                'icon' => 'ti tabler-scroll',
                'priority' => 60,
                'permissions' => ['quests.manage', 'guild.manage'],
                'is_system' => true,
                'is_bailiff' => true,
                'bailiff_tier' => 'Expeditions & Decrees',
                'users_count' => 1,
            ],
            [
                'id' => 11,
                'slug' => 'VETERAN',
                'name' => 'Veteran Adventurer',
                'description' => 'Hardened veterans who survived high-stakes guild expeditions.',
                'color' => '#00cfe8',
                'icon' => 'ti tabler-swords',
                'priority' => 40,
                'permissions' => [],
                'is_system' => false,
                'is_bailiff' => false,
                'users_count' => 22,
            ],
            [
                'id' => 12,
                'slug' => 'MEMBER',
                'name' => 'Guild Member',
                'description' => 'Fully initiated and sworn member of Valiant Shade.',
                'color' => '#28c76f',
                'icon' => 'ti tabler-medal',
                'priority' => 20,
                'permissions' => [],
                'is_system' => false,
                'is_bailiff' => false,
                'users_count' => 18,
            ],
            [
                'id' => 13,
                'slug' => 'PLAYER',
                'name' => 'Adventurer',
                'description' => 'Standard guild adventurer profile.',
                'color' => '#82868b',
                'icon' => 'ti tabler-user',
                'priority' => 10,
                'permissions' => [],
                'is_system' => true,
                'is_bailiff' => false,
                'users_count' => 273,
            ],
        ];
    }

    /**
     * Ensure storage file exists and contains all required default roles.
     */
    public function ensureInitialized(): void
    {
        $path = $this->getStoragePath();
        if (!file_exists($path)) {
            $this->saveRolesData($this->getDefaultRoles());
            return;
        }

        $existing = json_decode(file_get_contents($path), true);
        if (!is_array($existing) || empty($existing)) {
            $this->saveRolesData($this->getDefaultRoles());
            return;
        }

        $existingSlugs = array_column($existing, 'slug');
        $defaults = $this->getDefaultRoles();
        $updated = false;

        foreach ($defaults as $defaultRole) {
            if (!in_array($defaultRole['slug'], $existingSlugs, true)) {
                $maxId = 0;
                foreach ($existing as $er) {
                    if ((int)($er['id'] ?? 0) > $maxId) {
                        $maxId = (int)$er['id'];
                    }
                }
                $defaultRole['id'] = $maxId + 1;
                $existing[] = $defaultRole;
                $updated = true;
            } else {
                foreach ($existing as &$er) {
                    if ($er['slug'] === $defaultRole['slug']) {
                        if (!isset($er['is_bailiff']) && isset($defaultRole['is_bailiff'])) {
                            $er['is_bailiff'] = $defaultRole['is_bailiff'];
                            $updated = true;
                        }
                        if (!isset($er['bailiff_tier']) && isset($defaultRole['bailiff_tier'])) {
                            $er['bailiff_tier'] = $defaultRole['bailiff_tier'];
                            $updated = true;
                        }
                    }
                }
                unset($er);
            }
        }

        if ($updated) {
            $this->saveRolesData($existing);
        }
    }

    /**
     * Clear cached roles & permissions dataset (Cache Invalidation).
     */
    public function clearCache(): void
    {
        Cache::forget('valiant_shade.roles.all');
        Cache::forget('valiant_shade.permissions.catalog');
    }

    /**
     * Save roles array to JSON storage with Concurrency Safety and Atomic Exclusive File Locking (LOCK_EX).
     */
    protected function saveRolesData(array $roles): void
    {
        $path = $this->getStoragePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode(array_values($roles), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $tempPath = $path . '.' . uniqid('lock_', true) . '.tmp';

        $fp = fopen($tempPath, 'wb');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                fwrite($fp, $json);
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
                // Atomic file swap on operating system level prevents race conditions
                rename($tempPath, $path);
            } else {
                fclose($fp);
                @unlink($tempPath);
                file_put_contents($path, $json, LOCK_EX);
            }
        } else {
            file_put_contents($path, $json, LOCK_EX);
        }

        // Cache Invalidation
        $this->clearCache();
    }

    /**
     * Safely read JSON storage with Shared File Lock (LOCK_SH) to prevent reading during concurrent writes.
     */
    protected function readRolesDataSafe(): ?array
    {
        $path = $this->getStoragePath();
        if (!file_exists($path)) {
            return null;
        }

        $fp = fopen($path, 'rb');
        if (!$fp) {
            return null;
        }

        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if (empty($content)) {
            return null;
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }

    protected static ?bool $dbAvailable = null;

    /**
     * Check if database connection is alive with caching.
     */
    protected function isDbAvailable(): bool
    {
        if (static::$dbAvailable !== null) {
            return static::$dbAvailable;
        }

        try {
            if (class_exists(Role::class)) {
                DB::connection()->getPdo();
                static::$dbAvailable = true;
                return true;
            }
        } catch (\Throwable $e) {
            static::$dbAvailable = false;
        }

        return false;
    }

    /**
     * Get all roles with Cache-Aside Pattern and JSON/DB fallback.
     */
    public function getAllRoles(): Collection
    {
        $cached = Cache::get('valiant_shade.roles.all');
        if ($cached instanceof Collection) {
            return $cached;
        }

        if ($cached !== null) {
            Cache::forget('valiant_shade.roles.all');
        }

        $roles = null;
        // 1. Try DB first if alive
        if ($this->isDbAvailable()) {
            try {
                $dbRoles = Role::withCount('users')
                    ->orderByDesc('priority')
                    ->orderBy('id')
                    ->get();

                if ($dbRoles->isNotEmpty()) {
                    $roles = $dbRoles;
                }
            } catch (\Throwable $e) {
                static::$dbAvailable = false;
            }
        }

        if (!$roles) {
            // 2. Fall back to JSON storage with safe shared-lock read
            $this->ensureInitialized();
            $decoded = $this->readRolesDataSafe();
            if (is_array($decoded) && !empty($decoded)) {
                $roles = collect($decoded)->sortByDesc('priority')->values();
            } else {
                $roles = collect($this->getDefaultRoles());
            }
        }

        Cache::put('valiant_shade.roles.all', $roles, 3600);

        return $roles;
    }

    /**
     * Find a role by ID or Slug.
     */
    public function findRole(int|string $identifier): ?array
    {
        $roles = $this->getAllRoles();
        foreach ($roles as $r) {
            $rArr = is_array($r) ? $r : $r->toArray();
            if ((string) ($rArr['id'] ?? '') === (string) $identifier || ($rArr['slug'] ?? '') === (string) $identifier) {
                return $rArr;
            }
        }
        return null;
    }

    /**
     * Create a new role.
     */
    public function createRole(array $data): array
    {
        $slug = strtoupper(Str::slug($data['name'], '_'));
        if (empty($slug)) {
            $slug = 'ROLE_' . Str::upper(Str::random(6));
        }

        $allRoles = $this->getAllRoles()->map(fn($r) => is_array($r) ? $r : $r->toArray())->all();

        // Check uniqueness
        foreach ($allRoles as $r) {
            if ($r['slug'] === $slug) {
                throw new \InvalidArgumentException(__('A role with this name or slug already exists.'));
            }
        }

        // Try DB first if alive
        if ($this->isDbAvailable()) {
            try {
                if (class_exists(Role::class)) {
                    $role = Role::create([
                        'slug' => $slug,
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                        'color' => $data['color'] ?? '#7367f0',
                        'icon' => $data['icon'] ?? 'ti tabler-shield',
                        'priority' => (int) ($data['priority'] ?? 10),
                        'permissions' => $data['permissions'] ?? [],
                        'is_system' => false,
                    ]);
                    $created = $role->toArray();
                    $created['users_count'] = 0;
                    return $created;
                }
            } catch (\Throwable $e) {
                static::$dbAvailable = false;
            }
        }

        $maxId = 0;
        foreach ($allRoles as $r) {
            if ((int)$r['id'] > $maxId) {
                $maxId = (int)$r['id'];
            }
        }

        $isBailiff = !empty($data['is_bailiff']) || str_starts_with($slug, 'BAILIFF');
        $bailiffTier = $data['bailiff_tier'] ?? ($isBailiff ? 'Bailiff Sub-tier' : null);

        $newRole = [
            'id' => $maxId + 1,
            'slug' => $slug,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? '#7367f0',
            'icon' => $data['icon'] ?? 'ti tabler-shield',
            'priority' => (int) ($data['priority'] ?? 10),
            'permissions' => $data['permissions'] ?? [],
            'is_system' => false,
            'is_bailiff' => $isBailiff,
            'bailiff_tier' => $bailiffTier,
            'users_count' => 0,
        ];

        $allRoles[] = $newRole;
        $this->saveRolesData($allRoles);

        return $newRole;
    }

    /**
     * Update an existing role.
     */
    public function updateRole(int|string $identifier, array $data): array
    {
        $allRoles = $this->getAllRoles()->map(fn($r) => is_array($r) ? $r : $r->toArray())->all();
        $targetIndex = null;

        foreach ($allRoles as $idx => $r) {
            if ((string)$r['id'] === (string)$identifier || $r['slug'] === (string)$identifier) {
                $targetIndex = $idx;
                break;
            }
        }

        if ($targetIndex === null) {
            throw new \InvalidArgumentException(__('Role not found.'));
        }

        $current = $allRoles[$targetIndex];

        // Administrator protection: cannot drop roles.manage
        if ($current['slug'] === 'ADMIN') {
            $perms = $data['permissions'] ?? $current['permissions'] ?? [];
            if (!in_array('roles.manage', $perms, true)) {
                $perms[] = 'roles.manage';
            }
            $data['permissions'] = array_values(array_unique($perms));
        }

        // Try DB first if alive
        if ($this->isDbAvailable()) {
            try {
                if (class_exists(Role::class)) {
                    $roleModel = Role::where('id', $current['id'])->orWhere('slug', $current['slug'])->first();
                    if ($roleModel) {
                        $roleModel->update([
                            'name' => $data['name'] ?? $roleModel->name,
                            'description' => $data['description'] ?? $roleModel->description,
                            'color' => $data['color'] ?? $roleModel->color,
                            'icon' => $data['icon'] ?? $roleModel->icon,
                            'priority' => isset($data['priority']) ? (int) $data['priority'] : $roleModel->priority,
                            'permissions' => $data['permissions'] ?? $roleModel->permissions,
                        ]);
                        $fresh = $roleModel->fresh()->toArray();
                        $fresh['users_count'] = $current['users_count'] ?? 0;
                        return $fresh;
                    }
                }
            } catch (\Throwable $e) {
                static::$dbAvailable = false;
            }
        }

        if (isset($data['name'])) $current['name'] = $data['name'];
        if (isset($data['description'])) $current['description'] = $data['description'];
        if (isset($data['color'])) $current['color'] = $data['color'];
        if (isset($data['icon'])) $current['icon'] = $data['icon'];
        if (isset($data['priority'])) $current['priority'] = (int) $data['priority'];
        if (isset($data['permissions'])) $current['permissions'] = array_values($data['permissions']);
        if (array_key_exists('is_bailiff', $data)) $current['is_bailiff'] = (bool) $data['is_bailiff'];
        if (array_key_exists('bailiff_tier', $data)) $current['bailiff_tier'] = $data['bailiff_tier'];

        $allRoles[$targetIndex] = $current;
        $this->saveRolesData($allRoles);

        return $current;
    }

    /**
     * Toggle a specific permission for a role.
     */
    public function togglePermission(int|string $identifier, string $permission, bool $enabled): array
    {
        $role = $this->findRole($identifier);
        if (!$role) {
            throw new \InvalidArgumentException(__('Role not found.'));
        }

        $permissions = $role['permissions'] ?? [];

        if ($enabled) {
            if (!in_array($permission, $permissions, true)) {
                $permissions[] = $permission;
            }
        } else {
            // Guard admin from losing roles.manage
            if ($role['slug'] === 'ADMIN' && $permission === 'roles.manage') {
                throw new \InvalidArgumentException(__('The Administrator role must retain role management authority.'));
            }
            $permissions = array_values(array_filter($permissions, fn($p) => $p !== $permission));
        }

        return $this->updateRole($identifier, ['permissions' => $permissions]);
    }

    /**
     * Delete a custom role.
     */
    public function deleteRole(int|string $identifier): bool
    {
        $role = $this->findRole($identifier);
        if (!$role) {
            return false;
        }

        if (!empty($role['is_system'])) {
            throw new \InvalidArgumentException(__('Built-in system roles cannot be deleted.'));
        }

        if (!empty($role['users_count']) && (int)$role['users_count'] > 0) {
            throw new \InvalidArgumentException(__('You cannot delete a role that still has adventurers assigned to it. Reassign users first.'));
        }

        if ($this->isDbAvailable()) {
            try {
                if (class_exists(Role::class)) {
                    $roleModel = Role::where('id', $role['id'])->orWhere('slug', $role['slug'])->first();
                    if ($roleModel) {
                        $roleModel->delete();
                    }
                }
            } catch (\Throwable $e) {
                static::$dbAvailable = false;
            }
        }

        $allRoles = $this->getAllRoles()->map(fn($r) => is_array($r) ? $r : $r->toArray())->all();
        $filtered = array_filter($allRoles, fn($r) => (string)$r['id'] !== (string)$role['id'] && $r['slug'] !== $role['slug']);
        $this->saveRolesData($filtered);

        return true;
    }
}
