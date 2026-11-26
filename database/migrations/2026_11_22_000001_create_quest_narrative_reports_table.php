<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quest_narrative_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->date('quest_date')->nullable();
            $table->text('game_master')->nullable();
            $table->text('reporter')->nullable();
            $table->text('players')->nullable();
            $table->text('KIA')->nullable();
            $table->text('MIA')->nullable();
            $table->text('loot')->nullable();
            $table->text('expenses')->nullable();
            $table->longText('report');
            $table->text('search_title');
            $table->longText('search_content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_narrative_reports');
    }
};