<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Valiant Shade Parti Envanteri');
            $table->string('slug')->unique();
            $table->decimal('total_value', 14, 2)->default(0);
            $table->decimal('estimated_value', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::table('inventories')->insert([
            'name' => 'Valiant Shade Parti Envanteri',
            'slug' => 'party',
            'total_value' => 0,
            'estimated_value' => 0,
            'notes' => 'Lonca/parti ortak eşya stoğu (Pathfinder 1e).',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
