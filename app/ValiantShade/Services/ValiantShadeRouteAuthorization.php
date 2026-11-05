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
        'logout',
        'valiant-shade.auth-logout',
    ];

    /** @var list<string> */
    private const MODULE_ROUTE_PREFIXES = [
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
        'valiant-shade.app-user' => 'users',
        'valiant-shade.app-roles' => 'roles',
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
     * @return '__panel__' Guild panel (module read or bailiff/admin)
     * @return list<string> Any one of these permissions (module.action)
     */
    public function accessRule(?string $routeName): null|string|array
    {
        if ($routeName === null) {
            return '__panel__';
        }

        foreach (self::MEMBER_ROUTE_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return null;
            }
        }

        if (str_starts_with($routeName, 'valiant-shade.dashboard')) {
            return '__panel__';
        }

        foreach (self::MODULE_ROUTE_PREFIXES as $prefix => $module) {
            if (str_starts_with($routeName, $prefix)) {
                return $this->permissionsForModule($module);
            }
        }

        if (str_starts_with($routeName, 'valiant-shade.app-health')
            || str_starts_with($routeName, 'valiant-shade.app-audit-logs')) {
            return ['roles.all'];
        }

        return '__panel__';
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
