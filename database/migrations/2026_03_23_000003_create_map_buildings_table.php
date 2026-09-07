<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('territory_map_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_type_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->unsignedSmallInteger('grid_x');
            $table->unsignedSmallInteger('grid_y');
            $table->unsignedSmallInteger('rotation')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['territory_map_id', 'grid_x', 'grid_y']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_buildings');
    }
};
