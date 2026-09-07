<?php

namespace Database\Seeders;

use App\Models\BuildingType;
use App\Models\MapBuilding;
use App\Models\MapPortal;
use App\Models\MapTileType;
use App\Models\TerritoryMap;
use App\Support\Map\IsenburgTerrainBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class IsenburgMapSeeder extends Seeder
{
    public function run(): void
    {
        $width = 36;
        $height = 28;

        $terrainPath = 'maps/isonburg/terrain.json';
        $absolutePath = public_path($terrainPath);
        File::ensureDirectoryExists(dirname($absolutePath));
        File::put(
            $absolutePath,
            json_encode(IsenburgTerrainBuilder::build($width, $height), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $map = TerritoryMap::query()->updateOrCreate(
            ['slug' => 'isonburg'],
            [
                'name' => 'Isonburg Krallığı',
                'width_tiles' => $width,
                'height_tiles' => $height,
                'tile_size' => 32,
                'terrain_path' => $terrainPath,
            ]
        );

        MapTileType::query()->where('territory_map_id', $map->id)->delete();

        $tileTypeDefs = [
            ['slug' => 'grass', 'name' => 'Çim', 'fill' => '#5a9448', 'stroke' => '#4a7a3a'],
            ['slug' => 'grass-dark', 'name' => 'Koyu Çim', 'fill' => '#4a7a3a', 'stroke' => '#3d6630'],
            ['slug' => 'dirt', 'name' => 'Toprak Yol', 'fill' => '#c4a56a', 'stroke' => '#9a7848'],
            ['slug' => 'stone', 'name' => 'Taş Yol', 'fill' => '#b8b0a4', 'stroke' => '#8a8278'],
            ['slug' => 'water', 'name' => 'Su', 'fill' => '#3a7ca5', 'stroke' => '#2a5f82'],
            ['slug' => 'forest', 'name' => 'Orman', 'fill' => '#2d5a28', 'stroke' => '#1f401c'],
            ['slug' => 'field', 'name' => 'Tarla', 'fill' => '#8b6914', 'stroke' => '#6b5010'],
        ];

        $builderToDbId = [];
        foreach ($tileTypeDefs as $index => $def) {
            $tileType = MapTileType::query()->create([
                'territory_map_id' => $map->id,
                'slug' => $def['slug'],
                'name' => $def['name'],
                'fill_color' => $def['fill'],
                'stroke_color' => $def['stroke'],
                'sort_order' => $index,
            ]);
            $builderToDbId[$index + 1] = $tileType->id;
        }

        $builderData = IsenburgTerrainBuilder::build($width, $height)['layers'][0]['data'];
        $terrainData = array_map(
            fn (int $builderId) => $builderToDbId[$builderId] ?? $builderToDbId[1],
            $builderData,
        );

        $map->update(['terrain_data' => $terrainData]);

        MapPortal::query()->where('territory_map_id', $map->id)->delete();
        MapPortal::query()->create([
            'territory_map_id' => $map->id,
            'name' => 'Valiant Shade Arazisi',
            'target_map_slug' => 'valiant-shade',
            'grid_x' => 33,
            'grid_y' => 12,
            'width_tiles' => 3,
            'height_tiles' => 5,
            'description' => 'Doğu yolu — lonca arazisine dön',
            'is_active' => true,
        ]);

        $cabinType = BuildingType::query()->updateOrCreate(
            ['slug' => 'valiant-shade-kulube'],
            [
                'name' => 'Valiant Shade Kulübesi',
                'description' => 'Loncanın Isonburg yakınındaki ufak kulübesi. Buradan sola gidilince şehre ulaşılır.',
                'width_tiles' => 2,
                'height_tiles' => 2,
                'icon' => '🛖',
                'category' => 'core',
                'is_unique' => true,
                'is_draggable' => true,
                'is_active' => true,
            ]
        );

        BuildingType::query()->updateOrCreate(
            ['slug' => 'isonburg-kale'],
            [
                'name' => 'Isonburg Kalesi',
                'description' => 'Krallığın merkez yapısı.',
                'width_tiles' => 4,
                'height_tiles' => 4,
                'icon' => '🏰',
                'category' => 'core',
                'is_unique' => true,
                'is_draggable' => true,
                'is_active' => true,
            ]
        );

        $castleType = BuildingType::query()->where('slug', 'isonburg-kale')->first();

        MapBuilding::query()->where('territory_map_id', $map->id)->delete();

        MapBuilding::query()->create([
            'territory_map_id' => $map->id,
            'building_type_id' => $cabinType->id,
            'label' => 'Valiant Shade Kulübesi',
            'grid_x' => 27,
            'grid_y' => 19,
            'rotation' => 0,
            'is_active' => true,
        ]);

        if ($castleType) {
            MapBuilding::query()->create([
                'territory_map_id' => $map->id,
                'building_type_id' => $castleType->id,
                'label' => 'Isonburg',
                'grid_x' => 20,
                'grid_y' => 11,
                'rotation' => 0,
                'is_active' => true,
            ]);
        }
    }
}
