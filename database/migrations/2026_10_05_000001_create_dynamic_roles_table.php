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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->json('permissions');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('PLAYER')->change();
        });

        $permissionKeys = array_keys(\App\Models\Role::permissionCatalog());
        $now = now();
        $roles = [
            ['slug' => 'ADMIN', 'name' => 'Admin', 'permissions' => $permissionKeys],
            ['slug' => 'GM', 'name' => 'Game Master', 'permissions' => ['quests.manage']],
            ['slug' => 'PLAYER', 'name' => 'Player', 'permissions' => []],
            ['slug' => 'BAILIFF', 'name' => 'Bailiff', 'permissions' => []],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert([
                'slug' => $role['slug'],
                'name' => $role['name'],
                'permissions' => json_encode($role['permissions']),
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (DB::table('users')->get(['id', 'role']) as $user) {
            $legacyRole = trim((string) $user->role);
            $slug = match (strtoupper($legacyRole)) {
                'ADMIN' => 'ADMIN',
                'GM', 'GAME MASTER', 'GAMEMASTER' => 'GM',
                'BAILIFF' => 'BAILIFF',
                'PLAYER', 'USER', '' => 'PLAYER',
                default => Str::upper(Str::slug($legacyRole, '_')),
            };

            if (!DB::table('roles')->where('slug', $slug)->exists()) {
                DB::table('roles')->insert([
                    'slug' => $slug,
                    'name' => $legacyRole,
                    'permissions' => json_encode([]),
                    'is_system' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('users')->where('id', $user->id)->update(['role' => $slug]);
        }
    }

    public function down(): void
    {
        DB::table('users')->whereNotIn('role', ['ADMIN', 'GM', 'PLAYER'])->update(['role' => 'PLAYER']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['ADMIN', 'GM', 'PLAYER'])->default('PLAYER')->change();
        });

        Schema::dropIfExists('roles');
    }
};