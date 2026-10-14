<?php

namespace App\Services\Map;

use App\Models\MapTileType;
use App\Models\TerritoryMap;
use Illuminate\Support\Collection;

class MapBootstrapService
{
    /** @var array<int, array{slug:string,name:string,fill:string,stroke:string}> */
    private const DEFAULT_TILE_TYPES = [
        ['slug' => 'grass', 'name' => 'Çim', 'fill' => '#5a9448', 'stroke' => '#4a7a3a'],
        ['slug' => 'grass-dark', 'name' => 'Koyu Çim', 'fill' => '#4a7a3a', 'stroke' => '#3d6630'],
        ['slug' => 'dirt', 'name' => 'Toprak Yol', 'fill' => '#c4a56a', 'stroke' => '#9a7848'],
        ['slug' => 'stone', 'name' => 'Taş Yol', 'fill' => '#b8b0a4', 'stroke' => '#8a8278'],
        ['slug' => 'water', 'name' => 'Su', 'fill' => '#3a7ca5', 'stroke' => '#2a5f82'],
        ['slug' => 'forest', 'name' => 'Orman', 'fill' => '#2d5a28', 'stroke' => '#1f401c'],
        ['slug' => 'field', 'name' => 'Tarla', 'fill' => '#8b6914', 'stroke' => '#6b5010'],
    ];

    public function ensureTileTypes(TerritoryMap $map): Collection
    {
        if ($map->tileTypes()->exists()) {
            return $map->tileTypes()->orderBy('sort_order')->get();
        }

        foreach (self::DEFAULT_TILE_TYPES as $index => $def) {
            MapTileType::query()->create([
                'territory_map_id' => $map->id,
                'slug' => $def['slug'],
                'name' => $def['name'],
                'fill_color' => $def['fill'],
                'stroke_color' => $def['stroke'],
                'sort_order' => $index,
            ]);
        }

        return $map->tileTypes()->orderBy('sort_order')->get();
    }

    /** @param  array<int,int>  $data
     * @return array<int,int>
     */
    public function normalizeTerrainData(array $data, Collection $tileTypes): array
    {
        if ($tileTypes->isEmpty()) {
            return $data;
        }

        $validIds = $tileTypes->pluck('id')->all();
        $needsRemap = collect($data)->contains(
            fn (int $id) => $id !== 0 && ! in_array($id, $validIds, true),
        );

        if (! $needsRemap) {
            return $data;
        }

        $builderToDb = [];
        foreach (self::DEFAULT_TILE_TYPES as $index => $def) {
            $type = $tileTypes->firstWhere('slug', $def['slug']);
            $builderToDb[$index + 1] = $type?->id ?? $tileTypes->first()->id;
        }

        return array_map(
            fn (int $id) => $id === 0 ? 0 : ($builderToDb[$id] ?? $tileTypes->first()->id),
            $data,
        );
    }
}
