<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->nullable()->constrained('inventories')->nullOnDelete();
            $table->foreignId('inventory_item_type_id')->nullable()->constrained('inventory_item_types')->nullOnDelete();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->string('slug')->nullable();
            $table->string('quality')->nullable();
            $table->unsignedSmallInteger('uses_remaining')->nullable();
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('total_price', 14, 2)->default(0);
            $table->decimal('market_value', 14, 2)->default(0); // Ederi (%50)
            $table->decimal('buy_price', 14, 2)->default(0);    // Alım (Birim Ederi)
            $table->text('notes')->nullable();
            $table->tinyInteger('status')->default(0); // enums/.php dosyasına bakıp

            $table->index('inventory_item_type_id');
            $table->index('slug');
            $table->index('status');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};