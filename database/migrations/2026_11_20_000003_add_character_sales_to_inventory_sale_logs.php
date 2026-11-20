<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_sale_logs', function (Blueprint $table) {
            $table->string('destination')->default('market');
            $table->foreignId('buyer_character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->decimal('character_balance_before_sp', 14, 2)->nullable();
            $table->decimal('character_balance_after_sp', 14, 2)->nullable();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE financial_ledger DROP CONSTRAINT IF EXISTS financial_ledger_transaction_type_check');
            DB::statement("ALTER TABLE financial_ledger ADD CONSTRAINT financial_ledger_transaction_type_check CHECK (transaction_type IN ('SALARY', 'QUEST_SHARE', 'VAULT_PURCHASE', 'DEPOSIT', 'WITHDRAWAL', 'PENALTY', 'ITEM_SALE', 'ITEM_SALE_REVERT'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE financial_ledger DROP CONSTRAINT IF EXISTS financial_ledger_transaction_type_check');
            DB::statement("ALTER TABLE financial_ledger ADD CONSTRAINT financial_ledger_transaction_type_check CHECK (transaction_type IN ('SALARY', 'QUEST_SHARE', 'VAULT_PURCHASE', 'DEPOSIT', 'WITHDRAWAL', 'PENALTY'))");
        }

        Schema::table('inventory_sale_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('buyer_character_id');
            $table->dropColumn(['destination', 'character_balance_before_sp', 'character_balance_after_sp']);
        });
    }
};
