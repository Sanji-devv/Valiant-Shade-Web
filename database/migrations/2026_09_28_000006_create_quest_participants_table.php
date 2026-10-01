<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quest_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quest_id')->constrained('quests')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();

            $table->decimal('earned_quest_xp', 12, 2)->default(0.00);
            $table->decimal('earned_report_xp', 12, 2)->default(0.00);
            $table->boolean('is_kia')->default(false);
            $table->boolean('is_mia')->default(false);

            $table->unique(['quest_id', 'character_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_participants');
    }
};