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
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE financial_ledger ALTER COLUMN character_id DROP NOT NULL;');
        }

        Schema::table('financial_ledger', function (Blueprint $table) {
            if (DB::getDriverName() !== 'pgsql') {
                $table->unsignedBigInteger('character_id')->nullable()->change();
            }

            if (!Schema::hasColumn('financial_ledger', 'guild_vault_id')) {
                $table->foreignId('guild_vault_id')
                    ->nullable()
                    ->after('character_id')
                    ->constrained('guild_vaults')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('financial_ledger', 'entity_type')) {
                $table->string('entity_type')->default('CHARACTER')->after('guild_vault_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_ledger', function (Blueprint $table) {
            if (Schema::hasColumn('financial_ledger', 'guild_vault_id')) {
                $table->dropForeign(['guild_vault_id']);
                $table->dropColumn('guild_vault_id');
            }
            if (Schema::hasColumn('financial_ledger', 'entity_type')) {
                $table->dropColumn('entity_type');
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE financial_ledger ALTER COLUMN character_id SET NOT NULL;');
        }
    }
};
