<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quest_report_xp_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quest_id')->constrained('quests')->cascadeOnDelete();
            $table->foreignId('character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->foreignId('player_xp_id')->nullable()->constrained('player_xps')->nullOnDelete();
            $table->string('participant_name');
            $table->string('participant_key');
            $table->decimal('quest_xp', 16, 4);
            $table->decimal('report_xp', 16, 4);
            $table->string('state');
            $table->timestamps();

            $table->unique(['quest_id', 'participant_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_report_xp_entries');
    }
};
