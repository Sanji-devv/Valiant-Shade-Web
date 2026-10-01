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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_type_id')->nullable()->constrained('inventroy_item_types')->nullOnDelete();
            $table->string('name');
            $table->string('display_name');
            $table->string('slug');
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('total_price', 14, 2)->default(0);
            $table->decimal('market_value', 14, 2)->default(0); // Ederi (%55)
            $table->decimal('buy_price', 14, 2)->default(0);    // Alım (Birim Ederi)
            $table->text('notes')->nullable();
            $table->tinyInteger('status')->default(0); // Enums.php dosyasına bakıp
            $table->timestamps();

            $table->index('inventory_item_type_id');
            $table->index('slug');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
