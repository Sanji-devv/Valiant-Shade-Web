<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('territory_maps', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedSmallInteger('width_tiles');
            $table->unsignedSmallInteger('height_tiles');
            $table->unsignedSmallInteger('tile_size')->default(32);
            $table->string('terrain_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territory_maps');
    }
};
