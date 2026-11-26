<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ValiantShadeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_without_module_permissions_can_open_every_sidebar_page(): void
    {
        $member = User::factory()->create([
            'role' => 'PLAYER',
            'status' => 1,
        ]);

        $this->assertFalse($member->hasPermission('inventory.read'));

        $routes = [
            '/valiant-shade',
            '/valiant-shade/app/user/list',
            '/valiant-shade/app/user/list-data',
            '/valiant-shade/pages/profile-user',
            '/valiant-shade/pages/inventory',
            '/valiant-shade/pages/news',
            '/valiant-shade/pages/xp-tracker',
            '/valiant-shade/pages/adventurer-info',
            '/valiant-shade/pages/quest-board',
            '/valiant-shade/pages/quest-reports',
            '/valiant-shade/pages/featntrait',
            '/valiant-shade/pages/account-settings-account',
        ];

        foreach ($routes as $route) {
            $this->actingAs($member)
                ->get($route)
                ->assertOk();
        }
    }

    public function test_module_mutations_still_require_the_module_permission(): void
    {
        $member = User::factory()->create([
            'role' => 'PLAYER',
            'status' => 1,
        ]);

        $this->actingAs($member)
            ->post('/valiant-shade/pages/inventory', [])
            ->assertForbidden();
    }

    public function test_non_admin_with_roles_permission_cannot_open_hidden_admin_pages(): void
    {
        $member = User::factory()->create([
            'role' => 'PLAYER',
            'status' => 1,
        ]);
        $member->givePermissionTo(Permission::findOrCreate('roles.all', 'web'));

        foreach ([
            '/valiant-shade/app/roles',
            '/valiant-shade/app/audit-logs',
            '/valiant-shade/app/health',
        ] as $route) {
            $this->actingAs($member)
                ->get($route)
                ->assertForbidden();
        }
    }

    public function test_admin_can_open_hidden_admin_pages(): void
    {
        $admin = User::factory()->create([
            'role' => 'ADMIN',
            'status' => 1,
        ]);

        foreach ([
            '/valiant-shade/app/roles',
            '/valiant-shade/app/audit-logs',
            '/valiant-shade/app/health',
        ] as $route) {
            $this->actingAs($admin)
                ->get($route)
                ->assertOk();
        }
    }

    public function test_sidebar_hides_collapsible_admin_menu_from_members_and_shows_it_to_admins(): void
    {
        $member = User::factory()->create([
            'role' => 'PLAYER',
            'status' => 1,
        ]);

        $this->actingAs($member)
            ->get('/valiant-shade/pages/archive')
            ->assertOk()
            ->assertSee('/valiant-shade/pages/npcs', false)
            ->assertSee('/valiant-shade/pages/archive', false)
            ->assertDontSee('/valiant-shade/app/roles', false)
            ->assertDontSee('/valiant-shade/app/audit-logs', false)
            ->assertDontSee('/valiant-shade/app/health', false);

        $admin = User::factory()->create([
            'role' => 'ADMIN',
            'status' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/valiant-shade/app/roles')
            ->assertOk()
            ->assertSee('menu-link menu-toggle', false)
            ->assertSee('/valiant-shade/app/roles', false)
            ->assertSee('/valiant-shade/app/audit-logs', false)
            ->assertSee('/valiant-shade/app/health', false);
    }
}
