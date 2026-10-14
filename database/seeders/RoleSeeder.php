<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\ValiantShade\Services\RoleService;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed all system and Bailiff Council roles with exact permissions.
     */
    public function run(): void
    {
        $roleService = app(RoleService::class);
        $defaultRoles = $roleService->getDefaultRoles();

        foreach ($defaultRoles as $roleData) {
            Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'] ?? null,
                    'color' => $roleData['color'] ?? '#7367f0',
                    'icon' => $roleData['icon'] ?? 'ti tabler-shield',
                    'priority' => $roleData['priority'] ?? 10,
                    'permissions' => $roleData['permissions'] ?? [],
                    'is_system' => !empty($roleData['is_system']),
                ]
            );
        }

        // Attach Admin user to ADMIN role
        $adminUser = User::where('email', 'admin@valiantshade.com')->first();
        $adminRole = Role::where('slug', 'ADMIN')->first();

        if ($adminUser && $adminRole) {
            $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
            $adminUser->updatePrimaryRole();
        }
    }
}
