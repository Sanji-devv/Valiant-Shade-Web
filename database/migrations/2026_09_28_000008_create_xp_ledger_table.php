<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xp_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('category', [
                'QUEST',
                'REPORT',
                'BAILIFF',
                'WIKI',
                'EX_CHAR',
                'RANK_FLOOR',
                'ADJUSTMENT'
            ]);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_ledger');
    }
};