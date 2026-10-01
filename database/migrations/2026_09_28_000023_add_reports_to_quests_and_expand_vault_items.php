<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Expand quests table with narrative report and summary columns
        Schema::table('quests', function (Blueprint $table) {
            if (!Schema::hasColumn('quests', 'report_summary')) {
                $table->text('report_summary')->nullable()->after('status');
            }
            if (!Schema::hasColumn('quests', 'full_report')) {
                $table->text('full_report')->nullable()->after('report_summary');
            }
        });

        // 2. Drop restrictive enum constraint on guild_vault_items to allow all item types
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE guild_vault_items DROP CONSTRAINT IF EXISTS guild_vault_items_item_type_check;");
            DB::statement("ALTER TABLE guild_vault_items ALTER COLUMN item_type TYPE VARCHAR(255);");
            DB::statement("ALTER TABLE guild_vault_items ALTER COLUMN item_type SET DEFAULT 'Misc';");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            if (Schema::hasColumn('quests', 'full_report')) {
                $table->dropColumn('full_report');
            }
            if (Schema::hasColumn('quests', 'report_summary')) {
                $table->dropColumn('report_summary');
            }
        });
    }
};
