<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->foreignId('corrects_npc_id')
                ->nullable()
                ->after('user_id')
                ->constrained('npcs')
                ->nullOnDelete();
            $table->index('corrects_npc_id');
        });
    }

    public function down(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('corrects_npc_id');
        });
    }
};