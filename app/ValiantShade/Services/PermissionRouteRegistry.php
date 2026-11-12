<?php

namespace App\ValiantShade\Services;

/**
 * Canonical permission modules (routes table seed source).
 * Titles preserved from the legacy RoleService permission catalog.
 */
class PermissionRouteRegistry
{
    public const ACTIONS = ['read', 'create', 'update', 'delete', 'all'];

    /**
     * @return list<array{key: string, name: string, description: string, icon: string, badge: string}>
     */
    public function definitions(): array
    {
        return [
            [
                'key' => 'users',
                'name' => 'User Management',
                'description' => 'List users, create accounts, delete accounts, and sync characters.',
                'icon' => 'ti tabler-users',
                'badge' => 'bg-label-primary',
            ],
            [
                'key' => 'roles',
                'name' => 'Roles & Permissions',
                'description' => 'Create roles, edit roles, and configure permission access.',
                'icon' => 'ti tabler-shield-lock',
                'badge' => 'bg-label-danger',
            ],
            [
                'key' => 'guild',
                'name' => 'Guild Governance',
                'description' => 'Manage guild level, bailiff council officers, and demographics.',
                'icon' => 'ti tabler-building-castle',
                'badge' => 'bg-label-warning',
            ],
            [
                'key' => 'inventory',
                'name' => 'Inventory & Vault',
                'description' => 'Manage guild vault reserves, items, and adventurer gear assignments.',
                'icon' => 'ti tabler-package',
                'badge' => 'bg-label-info',
            ],
            [
                'key' => 'xp',
                'name' => 'Adventurer XP Tracking',
                'description' => 'Award quick XP, advance adventurer ranks, and manage level thresholds.',
                'icon' => 'ti tabler-award',
                'badge' => 'bg-label-success',
            ],
            [
                'key' => 'discipline',
                'name' => 'Discipline & Conduct',
                'description' => 'Manage guild discipline, infractions, warnings, and code of conduct enforcement.',
                'icon' => 'ti tabler-gavel',
                'badge' => 'bg-label-danger',
            ],
            [
                'key' => 'hr',
                'name' => 'Hero Resources & Roster',
                'description' => 'Manage adventurer onboarding, rank advancement approvals, and hero personnel records.',
                'icon' => 'ti tabler-users-group',
                'badge' => 'bg-label-info',
            ],
            [
                'key' => 'quests',
                'name' => 'Quest Board & Decrees',
                'description' => 'Pin quest notices, edit decrees, update status, and broadcast to Discord.',
                'icon' => 'ti tabler-clipboard-list',
                'badge' => 'bg-label-secondary',
            ],
            [
                'key' => 'archives',
                'name' => 'Archives & Chronicles',
                'description' => 'Manage guild archives, chronicles, history, and official scrolls.',
                'icon' => 'ti tabler-books',
                'badge' => 'bg-label-primary',
            ],
            [
                'key' => 'diplomacy',
                'name' => 'Guild Representation & Diplomacy',
                'description' => 'Represent the guild in external affairs, pacts, and faction diplomacy.',
                'icon' => 'ti tabler-flag',
                'badge' => 'bg-label-success',
            ],
            [
                'key' => 'oaths',
                'name' => 'Oaths & Covenant Enforcement',
                'description' => 'Oversee sworn oaths, membership vows, and code of honor fidelity.',
                'icon' => 'ti tabler-shield-lock',
                'badge' => 'bg-label-danger',
            ],
        ];
    }

    /** @deprecated Legacy flat keys mapped to module.all for migration helpers */
    public function legacyManageKeyMap(): array
    {
        $map = [];
        foreach ($this->definitions() as $route) {
            $map[$route['key'] . '.manage'] = $route['key'] . '.all';
        }

        return $map;
    }
}
