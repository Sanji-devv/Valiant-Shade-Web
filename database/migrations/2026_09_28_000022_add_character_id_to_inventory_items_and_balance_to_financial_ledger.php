<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update inventory_items: bind directly to character_id
        Schema::table('inventory_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items', 'character_id')) {
                $table->foreignId('character_id')->nullable()->after('id')->constrained('characters')->cascadeOnDelete();
            }
            if (Schema::hasColumn('inventory_items', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        // 2. Update financial_ledger: add balance_after_sp if missing
        Schema::table('financial_ledger', function (Blueprint $table) {
            if (!Schema::hasColumn('financial_ledger', 'balance_after_sp')) {
                $table->decimal('balance_after_sp', 14, 2)->default(0.00)->after('amount_sp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'character_id')) {
                $table->dropForeign(['character_id']);
                $table->dropColumn('character_id');
            }
            if (!Schema::hasColumn('inventory_items', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('financial_ledger', function (Blueprint $table) {
            if (Schema::hasColumn('financial_ledger', 'balance_after_sp')) {
                $table->dropColumn('balance_after_sp');
            }
        });
    }
};
