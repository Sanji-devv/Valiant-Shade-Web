<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_sale_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Güvenli geri al token
            $table->foreignId('sold_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('proceeds_sp', 14, 2)->default(0);
            $table->decimal('vault_balance_before_sp', 14, 2)->default(0);
            $table->decimal('vault_balance_after_sp', 14, 2)->default(0);
            $table->json('items_snapshot');  // Satılan itemların tam kopyası
            $table->text('notes')->nullable();
            $table->timestamp('reverted_at')->nullable();
            $table->foreignId('reverted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // financial_ledger transaction_type enum'una ITEM_SALE ve ITEM_SALE_REVERT ekle
        // PostgreSQL'de string column kullandığımız için ek kontrol gerekmez
        // (transaction_type string olarak tanımlı, enum kısıtı yok)
        if (DB::getDriverName() === 'pgsql') {
            // PostgreSQL'de ENUM native değil, string olarak tutulduğu için değişiklik gerekmez
            // Sadece uygulama seviyesinde yeni değerler kullanılacak
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_sale_logs');
    }
};
