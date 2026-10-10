<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create Roles Table (Discord-like with color, icon, and authority priority)
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('color')->default('#82868b');
                $table->string('icon')->default('ti tabler-user');
                $table->integer('priority')->default(10); // Higher number = Higher authority (100 = Admin, 10 = Player)
                $table->json('permissions');
                $table->boolean('is_system')->default(false);
                $table->timestamps();
            });
        }

        // 2. Create User-Role Many-to-Many Pivot Table (Discord-style multi-roles)
        if (!Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'role_id']);
            });
        }

        // 3. Relax column type on legacy users.role to varchar for compatibility
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(255) USING role::VARCHAR(255);");
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'PLAYER';");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('PLAYER')->change();
            });
        }

        // 4. Seed Standard Discord-style Hierarchy Roles
        $permissionCatalog = [
            'users.manage' => 'Manage users',
            'roles.manage' => 'Manage roles and permissions',
            'guild.manage' => 'Manage guild overview and bailiffs',
            'inventory.manage' => 'Manage inventory and guild vault',
            'xp.manage' => 'Manage player experience',
            'quests.manage' => 'Manage quest board',
        ];
        $allPermissions = array_keys($permissionCatalog);
        $now = now();

        $defaultRoles = [
            [
                'slug' => 'ADMIN',
                'name' => 'Administrator',
                'description' => 'Supreme executive authority of Valiant Shade Guild.',
                'color' => '#ea5455',
                'icon' => 'ti tabler-crown',
                'priority' => 100,
                'permissions' => $allPermissions,
                'is_system' => true,
            ],
            [
                'slug' => 'GM',
                'name' => 'Game Master',
                'description' => 'Oversees expeditions, dungeon quests, and story chronicles.',
                'color' => '#7367f0',
                'icon' => 'ti tabler-wand',
                'priority' => 80,
                'permissions' => ['quests.manage', 'xp.manage', 'guild.manage'],
                'is_system' => true,
            ],
            [
                'slug' => 'BAILIFF',
                'name' => 'Bailiff',
                'description' => 'High Council judicial officer safeguarding guild vault & order.',
                'color' => '#ff9f43',
                'icon' => 'ti tabler-shield-check',
                'priority' => 60,
                'permissions' => ['guild.manage', 'inventory.manage'],
                'is_system' => true,
            ],
            [
                'slug' => 'VETERAN',
                'name' => 'Veteran Adventurer',
                'description' => 'Proven guild heroes with extensive expedition tenure.',
                'color' => '#00cfe8',
                'icon' => 'ti tabler-swords',
                'priority' => 40,
                'permissions' => [],
                'is_system' => false,
            ],
            [
                'slug' => 'MEMBER',
                'name' => 'Guild Member',
                'description' => 'Recognized full member of the Valiant Shade company.',
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

        foreach ($defaultRoles as $r) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $r['slug']],
                [
                    'name' => $r['name'],
                    'description' => $r['description'],
                    'color' => $r['color'],
                    'icon' => $r['icon'],
                    'priority' => $r['priority'],
                    'permissions' => json_encode($r['permissions']),
                    'is_system' => $r['is_system'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // 5. Populate user_role pivot table for existing users based on legacy role
        $roleMap = DB::table('roles')->pluck('id', 'slug')->toArray();

        $users = DB::table('users')->get(['id', 'role']);
        foreach ($users as $user) {
            $legacyRole = strtoupper(trim((string) $user->role));
            $roleSlug = match ($legacyRole) {
                'ADMIN' => 'ADMIN',
                'GM', 'GAME MASTER', 'GAMEMASTER' => 'GM',
                'BAILIFF' => 'BAILIFF',
                'VETERAN' => 'VETERAN',
                'MEMBER' => 'MEMBER',
                default => 'PLAYER',
            };

            // Attach user primary role
            if (isset($roleMap[$roleSlug])) {
                DB::table('role_user')->updateOrInsert(
                    ['user_id' => $user->id, 'role_id' => $roleMap[$roleSlug]],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }

            // In Discord, everyone also has the base @everyone / Adventurer role
            if ($roleSlug !== 'PLAYER' && isset($roleMap['PLAYER'])) {
                DB::table('role_user')->updateOrInsert(
                    ['user_id' => $user->id, 'role_id' => $roleMap['PLAYER']],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};