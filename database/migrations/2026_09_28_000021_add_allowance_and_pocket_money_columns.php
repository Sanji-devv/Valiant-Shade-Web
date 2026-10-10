<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('characters')) {
            Schema::table('characters', function (Blueprint $table) {
                if (!Schema::hasColumn('characters', 'current_level')) {
                    $table->integer('current_level')->default(1);
                }
                if (!Schema::hasColumn('characters', 'allowance_sp')) {
                    $table->decimal('allowance_sp', 14, 2)->default(0.00);
                }
                if (!Schema::hasColumn('characters', 'total_xp')) {
                    $table->decimal('total_xp', 14, 2)->default(0.00);
                }
                if (!Schema::hasColumn('characters', 'rank_floor_xp')) {
                    $table->decimal('rank_floor_xp', 14, 2)->default(0.00);
                }
                if (!Schema::hasColumn('characters', 'quest_xp')) {
                    $table->decimal('quest_xp', 14, 2)->default(0.00);
                }
                if (!Schema::hasColumn('characters', 'report_xp')) {
                    $table->decimal('report_xp', 14, 2)->default(0.00);
                }
                if (!Schema::hasColumn('characters', 'bailiff_xp')) {
                    $table->decimal('bailiff_xp', 14, 2)->default(0.00);
                }
                if (!Schema::hasColumn('characters', 'ex_char_xp')) {
                    $table->decimal('ex_char_xp', 14, 2)->default(0.00);
                }
                if (!Schema::hasColumn('characters', 'wiki_xp')) {
                    $table->decimal('wiki_xp', 14, 2)->default(0.00);
                }
            });
        }

        if (Schema::hasTable('character_info') && !Schema::hasColumn('character_info', 'pocket_money')) {
            Schema::table('character_info', function (Blueprint $table) {
                $table->jsonb('pocket_money')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('characters')) {
            Schema::table('characters', function (Blueprint $table) {
                $cols = array_filter(
                    ['current_level', 'allowance_sp', 'total_xp', 'rank_floor_xp', 'quest_xp', 'report_xp', 'bailiff_xp', 'ex_char_xp', 'wiki_xp'],
                    fn($c) => Schema::hasColumn('characters', $c)
                );
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('character_info') && Schema::hasColumn('character_info', 'pocket_money')) {
            Schema::table('character_info', function (Blueprint $table) {
                $table->dropColumn('pocket_money');
            });
        }
    }
};
