<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\ValiantShade\Services\PermissionSyncService;
use App\ValiantShade\Services\RoleService;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionSyncService::class)->syncFromRoutesTable();

        $roleService = app(RoleService::class);
        $defaultRoles = $roleService->getDefaultRoles();

        foreach ($defaultRoles as $roleData) {
            $role = Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                [
                    'name' => $roleData['name'],
                    'guard_name' => 'web',
                    'description' => $roleData['description'] ?? null,
                    'color' => $roleData['color'] ?? '#7367f0',
                    'icon' => $roleData['icon'] ?? 'ti tabler-shield',
                    'priority' => $roleData['priority'] ?? 10,
                    'is_system' => ! empty($roleData['is_system']),
                ]
            );

            $role->syncPermissions($roleData['permissions'] ?? []);
        }

        $adminUser = User::where('email', 'admin@valiantshade.com')->first();
        $adminRole = Role::where('slug', 'ADMIN')->first();

        if ($adminUser && $adminRole) {
            $adminUser->assignRole($adminRole);
            $adminUser->updatePrimaryRole();
        }

        $roleService->clearCache();
    }
}
