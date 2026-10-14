<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_permission_role_and_assign_it_to_a_user(): void
    {
        $admin = $this->createUser('ADMIN');
        $playerId = $this->createUser('PLAYER')->id;

        $response = $this->actingAs($admin)->postJson('/valiant-shade/app/roles', [
            'name' => 'Quartermaster',
            'permissions' => ['inventory.all'],
        ]);

        $response->assertCreated()->assertJsonPath('role.slug', 'QUARTERMASTER');
        $roleId = $response->json('role.id');
        $this->putJson("/valiant-shade/app/roles/{$roleId}", [
            'name' => 'Senior Quartermaster',
            'permissions' => ['inventory.all', 'xp.all'],
        ])->assertOk();
        $this->putJson("/valiant-shade/app/user/{$playerId}/role", ['role' => 'QUARTERMASTER'])->assertOk();

        $assignedUser = User::findOrFail($playerId);
        $this->assertTrue($assignedUser->hasPermission('inventory.read'));
        $this->assertTrue($assignedUser->hasPermission('inventory.all'));
        $this->assertTrue($assignedUser->hasPermission('xp.all'));
        $this->assertFalse($assignedUser->hasPermission('users.read'));
    }

    public function test_role_manager_can_open_the_user_and_role_management_screen(): void
    {
        $admin = $this->createUser('ADMIN');

        $this->actingAs($admin)
            ->get('/valiant-shade/app/user/list')
            ->assertOk()
            ->assertSee('Manage Roles')
            ->assertSee('Role Management');
    }

    public function test_player_cannot_manage_roles(): void
    {
        $player = $this->createUser('PLAYER');

        $this->actingAs($player)
            ->postJson('/valiant-shade/app/roles', ['name' => 'Unauthorized', 'permissions' => []])
            ->assertForbidden();
    }

    private function createUser(string $role): User
    {
        $id = DB::table('users')->insertGetId([
            'username' => Str::lower(Str::random(12)),
            'email' => Str::lower(Str::random(12)) . '@example.test',
            'password' => 'hashed-password',
            'role' => $role,
            'status' => 2,
            'uuid' => (string) Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }
}
