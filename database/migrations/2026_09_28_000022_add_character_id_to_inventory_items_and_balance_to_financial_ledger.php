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
        // Zimmet character_inventory_assignments üzerinden; inventory_items lonca stoğu kalır.

        // Update financial_ledger: add balance_after_sp if missing
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
        Schema::table('financial_ledger', function (Blueprint $table) {
            if (Schema::hasColumn('financial_ledger', 'balance_after_sp')) {
                $table->dropColumn('balance_after_sp');
            }
        });
    }
};
