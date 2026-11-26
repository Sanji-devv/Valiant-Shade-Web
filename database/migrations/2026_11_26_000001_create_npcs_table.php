<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('npc_titles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->string('search_name', 255);
            $table->timestamps();
            $table->index('search_name');
        });

        Schema::create('npcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name', 255)->nullable();
            $table->string('mid_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('profession', 255)->nullable();
            $table->string('race', 120)->nullable();
            $table->string('faction', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->unsignedTinyInteger('status')->nullable();
            $table->string('approval_status', 32)->default('approved');
            $table->string('image_path', 500)->nullable();
            $table->text('summary')->nullable();
            $table->longText('background')->nullable();
            $table->text('notes')->nullable();
            $table->text('search_name');
            $table->longText('search_content');
            $table->timestamps();

            $table->index('approval_status');
            $table->index('status');
        });

        Schema::create('npc_npc_title', function (Blueprint $table) {
            $table->foreignId('npc_id')->constrained()->cascadeOnDelete();
            $table->foreignId('npc_title_id')->constrained('npc_titles')->cascadeOnDelete();
            $table->primary(['npc_id', 'npc_title_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('npc_npc_title');
        Schema::dropIfExists('npcs');
        Schema::dropIfExists('npc_titles');
    }
};