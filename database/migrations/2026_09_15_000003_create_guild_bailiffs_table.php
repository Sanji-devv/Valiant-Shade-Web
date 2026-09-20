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
        Schema::create('guild_bailiffs', function (Blueprint $table) {
            $table->id();
            $table->string('character_name');
            $table->string('role');                  // Kahraman Kaynakları, Envanter, Lonca Temsilcisi, Questmaster, Arşivci, Disiplin, Oathkeeper
            $table->string('assigned_date')->nullable(); // e.g. "14/7", "13/9"
            $table->string('status')->default('active'); // active, on_leave, retired
            $table->integer('order_index')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('character_name');
            $table->index('role');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guild_bailiffs');
    }
};
