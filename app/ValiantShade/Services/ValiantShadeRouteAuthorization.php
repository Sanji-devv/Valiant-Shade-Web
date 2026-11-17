<?php

namespace App\ValiantShade\Services;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Maps Valiant Shade panel routes to login / permission requirements.
 */
class ValiantShadeRouteAuthorization
{
    /** @var list<string> */
    private const PUBLIC_ROUTE_NAMES = [
        'valiant-shade.auth-login-basic',
        'valiant-shade.auth-login-basic.post',
        'valiant-shade.auth-register-basic',
        'valiant-shade.auth-register-basic.post',
        'valiant-shade.gods.index',
        'valiant-shade.gods.show',
        'valiant-shade.gods.update',
        'logout',
        'valiant-shade.auth-logout',
    ];

    /** @var list<string> */
    private const MODULE_ROUTE_PREFIXES = [
        'valiant-shade.app-user-list' => 'users',
        'valiant-shade.pages-inventory' => 'inventory',
        'valiant-shade.pages-xp-tracker' => 'xp',
        'valiant-shade.pages-adventurer-info' => 'guild',
        'valiant-shade.pages-maceraci-info' => 'guild',
        'valiant-shade.pages-guild-overview' => 'guild',
        'valiant-shade.pages-quest-board' => 'quests',
        'valiant-shade.pages-gorev-listesi' => 'quests',
        'valiant-shade.pages-quest-reports' => 'quests',
        'valiant-shade.pages-gorev-raporlari' => 'quests',
        'valiant-shade.pages-news' => 'archives',
        'valiant-shade.pages-guild-news' => 'archives',
        'valiant-shade.pages-featntrait' => 'hr',
        'valiant-shade.pages-feats' => 'hr',
        'valiant-shade.pages-traits' => 'hr',
        'valiant-shade.app-roles' => 'roles',
    ];

    /** @var list<string> */
    private const ADMIN_ONLY_ROUTE_PREFIXES = [
        'valiant-shade.app-roles',
        'valiant-shade.app-audit-logs',
        'valiant-shade.app-health',
    ];

    /** @var list<string> */
    private const MEMBER_VISIBLE_READ_ROUTE_NAMES = [
        'valiant-shade.dashboard-analytics',
        'valiant-shade.app-user-list',
        'valiant-shade.app-user-list.data',
    ];

    /** @var list<string> */
    private const MEMBER_VISIBLE_READ_ROUTE_PREFIXES = [
        'valiant-shade.pages-inventory',
        'valiant-shade.pages-news',
        'valiant-shade.pages-guild-news',
        'valiant-shade.pages-xp-tracker',
        'valiant-shade.pages-adventurer-info',
        'valiant-shade.pages-maceraci-info',
        'valiant-shade.pages-guild-overview',
        'valiant-shade.pages-quest-board',
        'valiant-shade.pages-gorev-listesi',
        'valiant-shade.pages-quest-reports',
        'valiant-shade.pages-gorev-raporlari',
        'valiant-shade.pages-featntrait',
        'valiant-shade.pages-feats-list',
        'valiant-shade.pages-feats-and-traits',
    ];

    /** @var list<string> */
    private const MEMBER_ROUTE_PREFIXES = [
        'valiant-shade.pages-profile',
        'valiant-shade.character',
        'valiant-shade.pages-account-settings-account',
    ];

    public function isPublic(Request $request, ?string $routeName): bool
    {
        if ($request->is('valiant-shade/lang/*')) {
            return true;
        }

        if ($routeName && in_array($routeName, self::PUBLIC_ROUTE_NAMES, true)) {
            return true;
        }

        return false;
    }

    /**
     * @return null Any authenticated member
     * @return '__admin__' Administrator-only page
     * @return '__panel__' Guild panel (module permission)
     * @return list<string> Any one of these permissions (module.action)
     */
    public function accessRule(?string $routeName, string $method = 'GET'): null|string|array
    {
        if ($routeName === null) {
            return '__panel__';
        }

        foreach (self::ADMIN_ONLY_ROUTE_PREFIXES as $prefix) {
            if ($this->matchesRoutePrefix($routeName, $prefix)) {
                return '__admin__';
            }
        }

        foreach (self::MEMBER_ROUTE_PREFIXES as $prefix) {
            if ($this->matchesRoutePrefix($routeName, $prefix)) {
                return null;
            }
        }

        if (in_array(strtoupper($method), ['GET', 'HEAD'], true)
            && $this->isMemberVisibleReadRoute($routeName)) {
            return null;
        }

        if (str_starts_with($routeName, 'valiant-shade.dashboard')) {
            return '__panel__';
        }

        foreach (self::MODULE_ROUTE_PREFIXES as $prefix => $module) {
            if ($this->matchesRoutePrefix($routeName, $prefix)) {
                return $this->permissionsForModule($module);
            }
        }

        return '__panel__';
    }

    private function isMemberVisibleReadRoute(string $routeName): bool
    {
        if (in_array($routeName, self::MEMBER_VISIBLE_READ_ROUTE_NAMES, true)) {
            return true;
        }

        foreach (self::MEMBER_VISIBLE_READ_ROUTE_PREFIXES as $prefix) {
            if ($this->matchesRoutePrefix($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function matchesRoutePrefix(string $routeName, string $prefix): bool
    {
        return $routeName === $prefix
            || str_starts_with($routeName, $prefix.'.')
            || str_starts_with($routeName, $prefix.'-');
    }

    /**
     * @return list<string>
     */
    private function permissionsForModule(string $module): array
    {
        return [
            "{$module}.read",
            "{$module}.create",
            "{$module}.update",
            "{$module}.delete",
            "{$module}.all",
        ];
    }

    public function hasPanelAccess(User $user): bool
    {
        if ($user->isAdmin() || $user->isGM() || $user->isBailiff()) {
            return true;
        }

        $registry = app(PermissionRouteRegistry::class);
        foreach ($registry->definitions() as $definition) {
            $key = $definition['key'];
            if ($user->hasPermission("{$key}.read") || $user->hasPermission("{$key}.all")) {
                return true;
            }
        }

        return false;
    }

    public function userMayAccess(User $user, null|string|array $rule): bool
    {
        if ($rule === null) {
            return true;
        }

        if ($rule === '__panel__') {
            return $this->hasPanelAccess($user);
        }

        if ($rule === '__admin__') {
            return $user->isAdmin();
        }

        if (is_array($rule)) {
            foreach ($rule as $permission) {
                if ($user->hasPermission($permission)) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
}
