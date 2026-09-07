<?php

namespace App\Services\Map;

use App\Models\MapTileType;
use App\Models\TerritoryMap;

class TerrainService
{
    /** @return array<int,int> */
    public function ensureTerrainData(TerritoryMap $map, int $defaultTileId = 1): array
    {
        if (is_array($map->terrain_data) && count($map->terrain_data) === $map->width_tiles * $map->height_tiles) {
            return $map->terrain_data;
        }

        $data = array_fill(0, $map->width_tiles * $map->height_tiles, $defaultTileId);
        $map->update(['terrain_data' => $data]);

        return $data;
    }

    /**
     * @param  array<int, array{x:int,y:int,tile_type_id:int}>  $tiles
     * @return array<int,int>
     */
    public function paintTiles(TerritoryMap $map, array $tiles): array
    {
        $data = $this->ensureTerrainData($map);
        $validIds = MapTileType::query()
            ->where('territory_map_id', $map->id)
            ->pluck('id')
            ->all();

        foreach ($tiles as $tile) {
            if (! in_array($tile['tile_type_id'], $validIds, true)) {
                continue;
            }
            if ($tile['x'] < 0 || $tile['y'] < 0 || $tile['x'] >= $map->width_tiles || $tile['y'] >= $map->height_tiles) {
                continue;
            }
            $data[($tile['y'] * $map->width_tiles) + $tile['x']] = $tile['tile_type_id'];
        }

        $map->update(['terrain_data' => $data]);

        return $data;
    }

    /** @return array<int,int> */
    public function resize(TerritoryMap $map, int $width, int $height, int $defaultTileId = 1): array
    {
        $oldWidth = $map->width_tiles;
        $oldHeight = $map->height_tiles;
        $oldData = $this->ensureTerrainData($map, $defaultTileId);

        $newData = array_fill(0, $width * $height, $defaultTileId);

        for ($y = 0; $y < min($oldHeight, $height); $y++) {
            for ($x = 0; $x < min($oldWidth, $width); $x++) {
                $newData[($y * $width) + $x] = $oldData[($y * $oldWidth) + $x];
            }
        }

        $map->update([
            'width_tiles' => $width,
            'height_tiles' => $height,
            'terrain_data' => $newData,
        ]);

        return $newData;
    }
}
