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
        // 1. Guild Vaults (Lonca Kasası - financial_ledger ve UserProfile bağımlılığı)
        if (!Schema::hasTable('guild_vaults')) {
            Schema::create('guild_vaults', function (Blueprint $table) {
                $table->id();
                $table->string('name')->default('Ana Lonca Kasası');
                $table->decimal('balance_sp', 14, 2)->default(0.00);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. Guild Settings (Lonca Seviye ve Puan Ayarları - AdventurerInfoSeeder bağımlılığı)
        if (!Schema::hasTable('guild_settings')) {
            Schema::create('guild_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        // 3. Guild Bailiffs (Lonca Görevlileri Meclisi - GuildBailiff Modeli ile tam uyum)
        if (!Schema::hasTable('guild_bailiffs')) {
            Schema::create('guild_bailiffs', function (Blueprint $table) {
                $table->id();
                $table->string('character_name');
                $table->string('role');
                $table->string('assigned_date')->nullable();
                $table->string('status')->default('active');
                $table->integer('order_index')->default(0);
                $table->text('notes')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index('character_name');
                $table->index('role');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guild_bailiffs');
        Schema::dropIfExists('guild_settings');
        Schema::dropIfExists('guild_vaults');
    }
};
