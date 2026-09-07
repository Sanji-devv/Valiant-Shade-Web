<?php

namespace Database\Seeders;

use App\Models\BuildingType;
use App\Models\MapBuilding;
use App\Models\MapTileType;
use App\Models\TerritoryMap;
use App\Support\Map\ValiantShadeTerrainBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ValiantShadeMapSeeder extends Seeder
{
    public function run(): void
    {
        $width = 40;
        $height = 30;

        $terrainPath = 'maps/valiant-shade/terrain.json';
        $absolutePath = public_path($terrainPath);
        File::ensureDirectoryExists(dirname($absolutePath));
        File::put(
            $absolutePath,
            json_encode(ValiantShadeTerrainBuilder::build($width, $height), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $map = TerritoryMap::query()->updateOrCreate(
            ['slug' => 'valiant-shade'],
            [
                'name' => 'Valiant Shade Arazisi',
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

        $builderData = ValiantShadeTerrainBuilder::build($width, $height)['layers'][0]['data'];
        $terrainData = array_map(
            fn (int $builderId) => $builderToDbId[$builderId] ?? $builderToDbId[1],
            $builderData,
        );

        $map->update(['terrain_data' => $terrainData]);

        $types = [
            ['slug' => 'kusane', 'name' => 'Kuşhane', 'description' => 'Mesaj kuşları barınağı.', 'w' => 1, 'h' => 1, 'icon' => '🕊️', 'category' => 'utility', 'unique' => true],
            ['slug' => 'merkezi-bina', 'name' => 'Merkezi Bina', 'description' => 'Lonca merkezi ve yönetim.', 'w' => 4, 'h' => 4, 'icon' => '🏛️', 'category' => 'core', 'unique' => true],
            ['slug' => 'drunken-shade', 'name' => 'Drunken Shade', 'description' => 'Lonca hanı ve dinlenme yeri.', 'w' => 4, 'h' => 3, 'icon' => '🍺', 'category' => 'core', 'unique' => true],
            ['slug' => 'tapinak', 'name' => 'Tapınak', 'description' => 'Kutsal ibadet ve ayinler.', 'w' => 3, 'h' => 4, 'icon' => '⛪', 'category' => 'core', 'unique' => true],
            ['slug' => 'mezarlik', 'name' => 'Mezarlık', 'description' => 'Düşenlerin anısına.', 'w' => 3, 'h' => 3, 'icon' => '🪦', 'category' => 'outdoor', 'unique' => true],
            ['slug' => 'ahir', 'name' => 'Ahır', 'description' => 'Binek hayvanları barınağı.', 'w' => 6, 'h' => 2, 'icon' => '🐴', 'category' => 'utility', 'unique' => true],
            ['slug' => 'aday-yatakhanesi', 'name' => 'Aday Yatakhanesi', 'description' => 'Aday üyelerin konaklaması.', 'w' => 3, 'h' => 3, 'icon' => '🛏️', 'category' => 'residential', 'unique' => true],
            ['slug' => 'uye-yatakhanesi', 'name' => 'Üye Yatakhanesi', 'description' => 'Üyelerin yatakhanesi.', 'w' => 3, 'h' => 3, 'icon' => '🏠', 'category' => 'residential', 'unique' => true],
            ['slug' => 'kidemli-yatakhanesi', 'name' => 'Kıdemli Yatakhanesi', 'description' => 'Kıdemli üyelerin konaklaması.', 'w' => 3, 'h' => 3, 'icon' => '🏰', 'category' => 'residential', 'unique' => true],
            ['slug' => 'uzman-evleri', 'name' => 'Uzman Evleri', 'description' => 'Uzmanların özel konutları.', 'w' => 4, 'h' => 4, 'icon' => '🏘️', 'category' => 'residential', 'unique' => true],
            ['slug' => 'hamam', 'name' => 'Hamam', 'description' => 'Temizlik ve dinlenme.', 'w' => 2, 'h' => 2, 'icon' => '🛁', 'category' => 'utility', 'unique' => true],
            ['slug' => 'demirci', 'name' => 'Demirci', 'description' => 'Silah ve zırh üretimi.', 'w' => 2, 'h' => 2, 'icon' => '⚒️', 'category' => 'industrial', 'unique' => true],
            ['slug' => 'revir', 'name' => 'Revir', 'description' => 'Yaralı bakım ve tedavi.', 'w' => 2, 'h' => 2, 'icon' => '💊', 'category' => 'utility', 'unique' => true],
            ['slug' => 'kutuphane', 'name' => 'Kütüphane', 'description' => 'Bilgi ve arşiv merkezi.', 'w' => 2, 'h' => 2, 'icon' => '📚', 'category' => 'utility', 'unique' => true],
            ['slug' => 'sera', 'name' => 'Sera', 'description' => 'Bitki yetiştirme alanı.', 'w' => 2, 'h' => 2, 'icon' => '🌱', 'category' => 'industrial', 'unique' => true],
            ['slug' => 'tarla', 'name' => 'Tarla', 'description' => 'Tarım alanları.', 'w' => 8, 'h' => 3, 'icon' => '🌾', 'category' => 'outdoor', 'unique' => true],
            ['slug' => 'marangoz', 'name' => 'Marangoz Atölyesi', 'description' => 'Ahşap işleri atölyesi.', 'w' => 2, 'h' => 2, 'icon' => '🪚', 'category' => 'industrial', 'unique' => true],
            ['slug' => 'simya', 'name' => 'Simya Laboratuvarı', 'description' => 'İksir ve simya çalışmaları.', 'w' => 3, 'h' => 2, 'icon' => '⚗️', 'category' => 'industrial', 'unique' => true],
            ['slug' => 'cardak', 'name' => 'Çardak', 'description' => 'Dinlenme çardağı.', 'w' => 1, 'h' => 1, 'icon' => '⛺', 'category' => 'outdoor', 'unique' => false],
            ['slug' => 'tabakhane', 'name' => 'Tabakhane', 'description' => 'Deri işleme atölyesi.', 'w' => 2, 'h' => 2, 'icon' => '🧵', 'category' => 'industrial', 'unique' => true],
            ['slug' => 'yel-degirmeni', 'name' => 'Yel Değirmeni', 'description' => 'Un öğütme değirmeni.', 'w' => 2, 'h' => 2, 'icon' => '🌀', 'category' => 'industrial', 'unique' => false],
            ['slug' => 'canavar-ehlilestirme', 'name' => 'Canavar Ehlileştirme Alanı', 'description' => 'Canavar ehlileştirme ve bakım alanı.', 'w' => 5, 'h' => 5, 'icon' => '🐉', 'category' => 'military', 'unique' => true],
            ['slug' => 'anit', 'name' => 'Anıt', 'description' => 'Anıt ve toplanma noktası.', 'w' => 1, 'h' => 1, 'icon' => '🗿', 'category' => 'outdoor', 'unique' => true],
            ['slug' => 'talim-alani', 'name' => 'Talim Alanı', 'description' => 'Eğitim ve dövüş talimi alanı.', 'w' => 5, 'h' => 4, 'icon' => '⚔️', 'category' => 'military', 'unique' => true],
            ['slug' => 'havuz', 'name' => 'Havuz', 'description' => 'Merkezi su havuzu.', 'w' => 2, 'h' => 2, 'icon' => '💧', 'category' => 'outdoor', 'unique' => true],
            ['slug' => 'su-kuyusu', 'name' => 'Su Kuyusu', 'description' => 'Temiz su kaynağı.', 'w' => 1, 'h' => 1, 'icon' => '🪣', 'category' => 'utility', 'unique' => false],
        ];

        $typeIds = [];
        foreach ($types as $type) {
            $model = BuildingType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'width_tiles' => $type['w'],
                    'height_tiles' => $type['h'],
                    'icon' => $type['icon'],
                    'category' => $type['category'],
                    'is_unique' => $type['unique'],
                    'is_draggable' => true,
                    'is_active' => true,
                ]
            );
            $typeIds[$type['slug']] = $model->id;
        }

        MapBuilding::query()->where('territory_map_id', $map->id)->delete();

        $placements = [
            ['type' => 'talim-alani', 'x' => 2, 'y' => 2],
            ['type' => 'canavar-ehlilestirme', 'x' => 1, 'y' => 13],
            ['type' => 'ahir', 'x' => 8, 'y' => 1],
            ['type' => 'tarla', 'x' => 15, 'y' => 1],
            ['type' => 'yel-degirmeni', 'x' => 13, 'y' => 5, 'label' => 'Yel Değirmeni 1'],
            ['type' => 'yel-degirmeni', 'x' => 17, 'y' => 5, 'label' => 'Yel Değirmeni 2'],
            ['type' => 'tapinak', 'x' => 22, 'y' => 2],
            ['type' => 'mezarlik', 'x' => 20, 'y' => 7],
            ['type' => 'merkezi-bina', 'x' => 19, 'y' => 11],
            ['type' => 'havuz', 'x' => 17, 'y' => 14],
            ['type' => 'anit', 'x' => 16, 'y' => 15],
            ['type' => 'drunken-shade', 'x' => 18, 'y' => 17],
            ['type' => 'aday-yatakhanesi', 'x' => 26, 'y' => 10],
            ['type' => 'uye-yatakhanesi', 'x' => 28, 'y' => 14],
            ['type' => 'kidemli-yatakhanesi', 'x' => 31, 'y' => 8],
            ['type' => 'uzman-evleri', 'x' => 33, 'y' => 12],
            ['type' => 'kusane', 'x' => 24, 'y' => 18],
            ['type' => 'demirci', 'x' => 13, 'y' => 20],
            ['type' => 'marangoz', 'x' => 11, 'y' => 22],
            ['type' => 'revir', 'x' => 9, 'y' => 18],
            ['type' => 'kutuphane', 'x' => 7, 'y' => 20],
            ['type' => 'sera', 'x' => 5, 'y' => 22],
            ['type' => 'simya', 'x' => 14, 'y' => 23],
            ['type' => 'tabakhane', 'x' => 10, 'y' => 24],
            ['type' => 'hamam', 'x' => 25, 'y' => 21],
            ['type' => 'su-kuyusu', 'x' => 20, 'y' => 13, 'label' => 'Su Kuyusu 1'],
            ['type' => 'su-kuyusu', 'x' => 32, 'y' => 16, 'label' => 'Su Kuyusu 2'],
            ['type' => 'cardak', 'x' => 15, 'y' => 16, 'label' => 'Çardak 1'],
            ['type' => 'cardak', 'x' => 29, 'y' => 17, 'label' => 'Çardak 2'],
        ];

        foreach ($placements as $placement) {
            MapBuilding::query()->create([
                'territory_map_id' => $map->id,
                'building_type_id' => $typeIds[$placement['type']],
                'label' => $placement['label'] ?? null,
                'grid_x' => $placement['x'],
                'grid_y' => $placement['y'],
                'rotation' => 0,
                'is_active' => true,
            ]);
        }
    }
}
