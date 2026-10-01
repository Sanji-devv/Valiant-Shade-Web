<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guild_vault_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('quality')->nullable();
            $table->string('item_type')->default('Misc');
            $table->integer('quantity')->default(1);

            // Pricing & Valuation (SP - Float / Numeric(12, 2))
            $table->decimal('unit_price_sp', 12, 2)->default(0.00);
            $table->decimal('total_price_sp', 12, 2)->default(0.00);
            $table->decimal('market_value_sp', 12, 2)->default(0.00);
            $table->decimal('purchase_cost_sp', 12, 2)->default(0.00);

            // Assignment & Main Character Link
            $table->foreignId('borrowed_by_character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->boolean('is_in_vault')->default(true);

            $table->timestamps();

            $table->index('item_name');
            $table->index('item_type');
            $table->index('is_in_vault');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guild_vault_items');
    }
};
