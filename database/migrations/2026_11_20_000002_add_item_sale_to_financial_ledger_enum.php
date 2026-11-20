<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * transaction_type sütunu PostgreSQL'de varchar olarak tutulduğu için
     * (Laravel enum() -> varchar), uygulama seviyesinde ITEM_SALE ve
     * ITEM_SALE_REVERT değerleri doğrudan kullanılabilir. Bu migration
     * yalnızca MySQL için sütunu genişletir.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE financial_ledger MODIFY COLUMN transaction_type ENUM('SALARY','QUEST_SHARE','VAULT_PURCHASE','DEPOSIT','WITHDRAWAL','PENALTY','ITEM_SALE','ITEM_SALE_REVERT') NOT NULL");
        }
        // PostgreSQL: varchar olduğu için herhangi bir değişiklik gerekmez.
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE financial_ledger MODIFY COLUMN transaction_type ENUM('SALARY','QUEST_SHARE','VAULT_PURCHASE','DEPOSIT','WITHDRAWAL','PENALTY') NOT NULL");
        }
    }
};
