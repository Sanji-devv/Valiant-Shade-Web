<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guild_vaults', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Valiant Shade Lonca Kasası');
            $table->decimal('balance_sp', 14, 2)->default(30000.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Seed initial row with 30,000 Silver (30k SP)
        DB::table('guild_vaults')->insert([
            'name' => 'Valiant Shade Lonca Kasası',
            'balance_sp' => 30000.00,
            'notes' => 'Parti ve loncanın fiziki nakit kasası.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('guild_vaults');
    }
};
