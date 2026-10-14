<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($tableNames), 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');

        if (! Schema::hasTable('routes')) {
            Schema::create('routes', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('icon')->default('ti tabler-shield');
                $table->string('badge')->default('bg-label-primary');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($tableNames['permissions'])) {
            Schema::create($tableNames['permissions'], static function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (! Schema::hasTable($tableNames['roles'])) {
            Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
                $table->id();
                if ($teams || config('permission.testing')) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                    $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
                }
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->text('description')->nullable();
                $table->string('color')->default('#82868b');
                $table->string('icon')->default('ti tabler-user');
                $table->integer('priority')->default(10);
                $table->boolean('is_system')->default(false);
                $table->timestamps();
                if ($teams || config('permission.testing')) {
                    $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
                } else {
                    $table->unique(['name', 'guard_name']);
                }
            });
        } else {
            Schema::table($tableNames['roles'], function (Blueprint $table) {
                if (! Schema::hasColumn('roles', 'guard_name')) {
                    $table->string('guard_name')->default('web')->after('name');
                }
            });
            DB::table('roles')->whereNull('guard_name')->orWhere('guard_name', '')->update(['guard_name' => 'web']);
        }

        if (! Schema::hasTable($tableNames['model_has_permissions'])) {
            Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
                $table->unsignedBigInteger($pivotPermission);
                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key']);
                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->foreign($pivotPermission)
                    ->references('id')
                    ->on($tableNames['permissions'])
                    ->cascadeOnDelete();
                if ($teams) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key']);
                    $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');
                    $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                        'model_has_permissions_permission_model_type_primary');
                } else {
                    $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                        'model_has_permissions_permission_model_type_primary');
                }
            });
        }

        if (! Schema::hasTable($tableNames['model_has_roles'])) {
            Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
                $table->unsignedBigInteger($pivotRole);
                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key']);
                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->foreign($pivotRole)
                    ->references('id')
                    ->on($tableNames['roles'])
                    ->cascadeOnDelete();
                if ($teams) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key']);
                    $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');
                    $table->primary([$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                        'model_has_roles_role_model_type_primary');
                } else {
                    $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                        'model_has_roles_role_model_type_primary');
                }
            });
        }

        if (! Schema::hasTable($tableNames['role_has_permissions'])) {
            Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
                $table->unsignedBigInteger($pivotPermission);
                $table->unsignedBigInteger($pivotRole);
                $table->foreign($pivotPermission)
                    ->references('id')
                    ->on($tableNames['permissions'])
                    ->cascadeOnDelete();
                $table->foreign($pivotRole)
                    ->references('id')
                    ->on($tableNames['roles'])
                    ->cascadeOnDelete();
                $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
            });
        }

        $this->seedRoutesTable();
        $this->syncPermissionRecords();
        $this->migrateLegacyRolePermissions();
        $this->migrateRoleUserPivot();

        if (Schema::hasColumn('roles', 'permissions')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('permissions');
            });
        }

        if (Schema::hasTable('role_user')) {
            Schema::drop('role_user');
        }

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    protected function seedRoutesTable(): void
    {
        $catalog = app(\App\ValiantShade\Services\PermissionRouteRegistry::class)->definitions();
        $now = now();

        foreach ($catalog as $sort => $route) {
            DB::table('routes')->updateOrInsert(
                ['key' => $route['key']],
                [
                    'name' => $route['name'],
                    'description' => $route['description'] ?? null,
                    'icon' => $route['icon'] ?? 'ti tabler-shield',
                    'badge' => $route['badge'] ?? 'bg-label-primary',
                    'sort_order' => $sort + 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    protected function syncPermissionRecords(): void
    {
        app(\App\ValiantShade\Services\PermissionSyncService::class)->syncFromRoutesTable();
    }

    protected function migrateLegacyRolePermissions(): void
    {
        if (! Schema::hasColumn('roles', 'permissions')) {
            return;
        }

        $permissionMap = [];
        foreach (DB::table('permissions')->get(['id', 'name']) as $perm) {
            $permissionMap[$perm->name] = $perm->id;
        }

        $roles = DB::table('roles')->get(['id', 'permissions']);
        foreach ($roles as $role) {
            $legacy = json_decode($role->permissions ?? '[]', true);
            if (! is_array($legacy)) {
                continue;
            }

            foreach ($legacy as $legacyPerm) {
                $target = str_ends_with($legacyPerm, '.manage')
                    ? str_replace('.manage', '.all', $legacyPerm)
                    : $legacyPerm;

                if (! isset($permissionMap[$target])) {
                    continue;
                }

                DB::table('role_has_permissions')->updateOrInsert(
                    [
                        'role_id' => $role->id,
                        'permission_id' => $permissionMap[$target],
                    ],
                    []
                );
            }
        }
    }

    protected function migrateRoleUserPivot(): void
    {
        if (! Schema::hasTable('role_user') || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        $userModel = app(\App\Models\User::class);
        $modelType = $userModel->getMorphClass();

        foreach (DB::table('role_user')->get() as $row) {
            DB::table('model_has_roles')->updateOrInsert(
                [
                    'role_id' => $row->role_id,
                    'model_id' => $row->user_id,
                    'model_type' => $modelType,
                ],
                []
            );
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        throw_if(empty($tableNames), 'Error: config/permission.php not found.');

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['permissions']);
        Schema::dropIfExists('routes');
    }
};
