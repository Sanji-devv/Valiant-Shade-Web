<?php

namespace App\ValiantShade\Services;

use App\Models\PermissionRoute;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSyncService
{
    public function __construct(
        protected PermissionRouteRegistry $registry,
    ) {}

    public function syncFromRoutesTable(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $routes = PermissionRoute::query()->where('is_active', true)->orderBy('sort_order')->get();

        if ($routes->isEmpty()) {
            foreach ($this->registry->definitions() as $index => $definition) {
                PermissionRoute::updateOrCreate(
                    ['key' => $definition['key']],
                    [
                        'name' => $definition['name'],
                        'description' => $definition['description'] ?? null,
                        'icon' => $definition['icon'] ?? 'ti tabler-shield',
                        'badge' => $definition['badge'] ?? 'bg-label-primary',
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }
            $routes = PermissionRoute::query()->where('is_active', true)->orderBy('sort_order')->get();
        }

        foreach ($routes as $route) {
            foreach (PermissionRouteRegistry::ACTIONS as $action) {
                Permission::findOrCreate("{$route->key}.{$action}", $guard);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
