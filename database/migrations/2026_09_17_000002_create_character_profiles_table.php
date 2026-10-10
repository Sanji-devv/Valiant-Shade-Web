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
        Schema::create('character_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('character_name')->unique();
            $table->string('player_name')->nullable();
            $table->string('race')->default('Human')->nullable();
            $table->string('class_name')->default('Adventurer')->nullable();
            $table->string('archetype')->nullable();
            $table->integer('level')->default(1);
            $table->string('rank')->default('Aday')->nullable();
            $table->integer('str')->default(10);
            $table->integer('dex')->default(10);
            $table->integer('con')->default(10);
            $table->integer('int')->default(10);
            $table->integer('wis')->default(10);
            $table->integer('cha')->default(10);
            $table->integer('hp')->nullable();
            $table->integer('max_hp')->nullable();
            $table->string('hit_die')->default('d10');
            $table->string('bab')->nullable();
            $table->string('fort_save')->nullable();
            $table->string('ref_save')->nullable();
            $table->string('will_save')->nullable();
            $table->text('bio')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('character_name');
            $table->index('class_name');
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_profiles');
    }
};
