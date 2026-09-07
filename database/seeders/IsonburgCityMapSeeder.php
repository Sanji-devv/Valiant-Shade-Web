<?php

namespace Database\Seeders;

use App\Models\MapPortal;
use App\Models\MapTileType;
use App\Models\TerritoryMap;
use Illuminate\Database\Seeder;

class IsonburgCityMapSeeder extends Seeder
{
    public function run(): void
    {
        $width = 48;
        $height = 36;

        $map = TerritoryMap::query()->updateOrCreate(
            ['slug' => 'isonburg-sehir'],
            [
                'name' => 'Isonburg Şehri',
                'width_tiles' => $width,
                'height_tiles' => $height,
                'tile_size' => 32,
                'terrain_path' => null,
            ]
        );

        MapTileType::query()->where('territory_map_id', $map->id)->delete();

        $tileTypeDefs = [
            ['slug' => 'grass', 'name' => 'Çim', 'fill' => '#5a9448', 'stroke' => '#4a7a3a'],
            ['slug' => 'stone', 'name' => 'Taş Yol', 'fill' => '#b8b0a4', 'stroke' => '#8a8278'],
            ['slug' => 'dirt', 'name' => 'Toprak Yol', 'fill' => '#c4a56a', 'stroke' => '#9a7848'],
            ['slug' => 'water', 'name' => 'Su', 'fill' => '#3a7ca5', 'stroke' => '#2a5f82'],
            ['slug' => 'forest', 'name' => 'Orman', 'fill' => '#2d5a28', 'stroke' => '#1f401c'],
        ];

        $tileIds = [];
        foreach ($tileTypeDefs as $index => $def) {
            $tileType = MapTileType::query()->create([
                'territory_map_id' => $map->id,
                'slug' => $def['slug'],
                'name' => $def['name'],
                'fill_color' => $def['fill'],
                'stroke_color' => $def['stroke'],
                'sort_order' => $index,
            ]);
            $tileIds[] = $tileType->id;
        }

        $defaultTile = $tileIds[0];
        $stoneTile = $tileIds[1];
        $waterTile = $tileIds[3];

        $terrainData = array_fill(0, $width * $height, $defaultTile);

        for ($y = 8; $y < 28; $y++) {
            for ($x = 10; $x < 38; $x++) {
                $terrainData[($y * $width) + $x] = $stoneTile;
            }
        }

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $terrainData[($y * $width) + $x] = $waterTile;
            }
        }

        $map->update(['terrain_data' => $terrainData]);

        MapPortal::query()->updateOrCreate(
            [
                'territory_map_id' => $map->id,
                'name' => 'Valiant Shade Arazisi',
            ],
            [
                'target_map_slug' => 'valiant-shade',
                'grid_x' => 40,
                'grid_y' => 28,
                'width_tiles' => 3,
                'height_tiles' => 3,
                'description' => 'Seedbead Row — kulübe',
                'is_active' => true,
            ]
        );
    }
}
