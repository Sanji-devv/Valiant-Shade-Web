<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_tile_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('territory_map_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('fill_color', 16);
            $table->string('stroke_color', 16);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['territory_map_id', 'slug']);
        });

        Schema::table('territory_maps', function (Blueprint $table) {
            $table->json('terrain_data')->nullable()->after('terrain_path');
        });
    }

    public function down(): void
    {
        Schema::table('territory_maps', function (Blueprint $table) {
            $table->dropColumn('terrain_data');
        });

        Schema::dropIfExists('map_tile_types');
    }
};
