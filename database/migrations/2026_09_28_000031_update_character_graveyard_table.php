<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('character_graveyard') && !Schema::hasColumn('character_graveyard', 'death_quest_id')) {
            Schema::table('character_graveyard', function (Blueprint $table) {
                $table->foreignId('death_quest_id')->nullable()->constrained('quests')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('character_graveyard') && Schema::hasColumn('character_graveyard', 'death_quest_id')) {
            Schema::table('character_graveyard', function (Blueprint $table) {
                $table->dropForeign(['death_quest_id']);
                $table->dropColumn('death_quest_id');
            });
        }
    }
};
