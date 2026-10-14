<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('map_portals')) {
            Schema::create('map_portals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('territory_map_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('target_map_slug');
                $table->unsignedSmallInteger('grid_x');
                $table->unsignedSmallInteger('grid_y');
                $table->unsignedSmallInteger('width_tiles')->default(2);
                $table->unsignedSmallInteger('height_tiles')->default(2);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['territory_map_id', 'is_active']);
                $table->foreign('target_map_slug')->references('slug')->on('territory_maps')->cascadeOnUpdate();
            });
        }

        if (Schema::hasColumn('territory_maps', 'portals')) {
            DB::table('territory_maps')
                ->whereNotNull('portals')
                ->orderBy('id')
                ->lazyById()
                ->each(function (object $map): void {
                    $portals = $map->portals;

                    if (is_string($portals)) {
                        $portals = json_decode($portals, true);
                    }

                    if (! is_array($portals)) {
                        return;
                    }

                    foreach ($portals as $portal) {
                        if (! is_array($portal)) {
                            continue;
                        }

                        $targetSlug = $portal['target_slug'] ?? $portal['target_map_slug'] ?? null;
                        if (! $targetSlug) {
                            continue;
                        }

                        DB::table('map_portals')->insert([
                            'territory_map_id' => $map->id,
                            'name' => $portal['label'] ?? $portal['name'] ?? 'Geçit',
                            'target_map_slug' => $targetSlug,
                            'grid_x' => $portal['x'] ?? $portal['grid_x'] ?? 0,
                            'grid_y' => $portal['y'] ?? $portal['grid_y'] ?? 0,
                            'width_tiles' => $portal['width'] ?? $portal['width_tiles'] ?? 2,
                            'height_tiles' => $portal['height'] ?? $portal['height_tiles'] ?? 2,
                            'description' => $portal['description'] ?? null,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });

            Schema::table('territory_maps', function (Blueprint $table) {
                $table->dropColumn('portals');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('map_portals');

        if (! Schema::hasColumn('territory_maps', 'portals')) {
            Schema::table('territory_maps', function (Blueprint $table) {
                $table->json('portals')->nullable()->after('terrain_path');
            });
        }
    }
};
