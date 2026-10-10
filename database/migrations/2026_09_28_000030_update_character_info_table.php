<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('character_info')) {
            Schema::table('character_info', function (Blueprint $table) {
                if (!Schema::hasColumn('character_info', 'str_mod')) {
                    $table->integer('str_mod')->default(0);
                }
                if (!Schema::hasColumn('character_info', 'dex_mod')) {
                    $table->integer('dex_mod')->default(0);
                }
                if (!Schema::hasColumn('character_info', 'con_mod')) {
                    $table->integer('con_mod')->default(0);
                }
                if (!Schema::hasColumn('character_info', 'int_mod')) {
                    $table->integer('int_mod')->default(0);
                }
                if (!Schema::hasColumn('character_info', 'wis_mod')) {
                    $table->integer('wis_mod')->default(0);
                }
                if (!Schema::hasColumn('character_info', 'cha_mod')) {
                    $table->integer('cha_mod')->default(0);
                }
                if (!Schema::hasColumn('character_info', 'temp_ability_mods')) {
                    $table->jsonb('temp_ability_mods')->nullable();
                }
                if (!Schema::hasColumn('character_info', 'hp_current')) {
                    $table->integer('hp_current')->default(10)->nullable();
                }
                if (!Schema::hasColumn('character_info', 'temp_hp')) {
                    $table->integer('temp_hp')->default(0)->nullable();
                }
                if (!Schema::hasColumn('character_info', 'nonlethal_damage')) {
                    $table->integer('nonlethal_damage')->default(0)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('character_info')) {
            Schema::table('character_info', function (Blueprint $table) {
                $columns = ['str_mod', 'dex_mod', 'con_mod', 'int_mod', 'wis_mod', 'cha_mod', 'temp_ability_mods', 'hp_current', 'temp_hp', 'nonlethal_damage'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('character_info', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
