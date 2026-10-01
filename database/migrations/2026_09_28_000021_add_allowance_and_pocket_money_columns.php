<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('characters') && !Schema::hasColumn('characters', 'allowance_sp')) {
            Schema::table('characters', function (Blueprint $table) {
                $table->decimal('allowance_sp', 14, 2)->default(0.00)->after('current_level');
            });
        }

        if (Schema::hasTable('character_info') && !Schema::hasColumn('character_info', 'pocket_money')) {
            Schema::table('character_info', function (Blueprint $table) {
                $table->jsonb('pocket_money')->nullable()->after('load_capacity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('characters') && Schema::hasColumn('characters', 'allowance_sp')) {
            Schema::table('characters', function (Blueprint $table) {
                $table->dropColumn('allowance_sp');
            });
        }

        if (Schema::hasTable('character_info') && Schema::hasColumn('character_info', 'pocket_money')) {
            Schema::table('character_info', function (Blueprint $table) {
                $table->dropColumn('pocket_money');
            });
        }
    }
};
