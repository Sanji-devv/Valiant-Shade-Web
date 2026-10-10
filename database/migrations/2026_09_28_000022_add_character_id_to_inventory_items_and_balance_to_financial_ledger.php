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
        // 1. inventory_items tablosunu InventoryItem modeliyle uyumlu hale getirme
        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_items', 'character_id')) {
                    $table->foreignId('character_id')->nullable()->after('id')->constrained('characters')->nullOnDelete();
                }
                if (!Schema::hasColumn('inventory_items', 'type')) {
                    $table->string('type')->nullable()->after('quality');
                }
                if (!Schema::hasColumn('inventory_items', 'quantity')) {
                    $table->integer('quantity')->default(1)->after('type');
                }
            });
        }

        // 2. Update financial_ledger: add balance_after_sp if missing
        if (Schema::hasTable('financial_ledger')) {
            Schema::table('financial_ledger', function (Blueprint $table) {
                if (!Schema::hasColumn('financial_ledger', 'balance_after_sp')) {
                    $table->decimal('balance_after_sp', 14, 2)->default(0.00)->after('amount_sp');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                if (Schema::hasColumn('inventory_items', 'character_id')) {
                    $table->dropForeign(['character_id']);
                    $table->dropColumn('character_id');
                }
                if (Schema::hasColumn('inventory_items', 'type')) {
                    $table->dropColumn('type');
                }
                if (Schema::hasColumn('inventory_items', 'quantity')) {
                    $table->dropColumn('quantity');
                }
            });
        }

        if (Schema::hasTable('financial_ledger')) {
            Schema::table('financial_ledger', function (Blueprint $table) {
                if (Schema::hasColumn('financial_ledger', 'balance_after_sp')) {
                    $table->dropColumn('balance_after_sp');
                }
            });
        }
    }
};
