<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('width_tiles')->default(1);
            $table->unsignedTinyInteger('height_tiles')->default(1);
            $table->string('icon', 16)->nullable();
            $table->string('sprite_path')->nullable();
            $table->string('category', 32)->default('utility');
            $table->boolean('is_unique')->default(true);
            $table->boolean('is_draggable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_types');
    }
};
