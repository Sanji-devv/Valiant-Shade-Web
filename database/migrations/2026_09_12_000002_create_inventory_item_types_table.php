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
        Schema::create('inventory_item_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name');
            $table->string('slug');
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('status')->default(0); // 0 closed / 1 active / 2 passive
            $table->timestamps();

            $table->index('slug');
            $table->index('name');
            $table->index('display_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_item_types');
    }
};
