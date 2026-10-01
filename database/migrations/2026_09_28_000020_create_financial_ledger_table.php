<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->decimal('amount_sp', 14, 2);
            $table->enum('transaction_type', [
                'SALARY',
                'QUEST_SHARE',
                'VAULT_PURCHASE',
                'DEPOSIT',
                'WITHDRAWAL',
                'PENALTY'
            ]);
            $table->foreignId('reference_vault_item_id')->nullable()->constrained('guild_vault_items')->nullOnDelete();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->index('character_id');
            $table->index('transaction_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_ledger');
    }
};
