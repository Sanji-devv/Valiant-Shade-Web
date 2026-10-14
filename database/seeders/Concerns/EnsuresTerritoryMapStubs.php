<?php

namespace Database\Seeders\Concerns;

use App\Models\TerritoryMap;

trait EnsuresTerritoryMapStubs
{
    /**
     * map_portals.target_map_slug FK requires the target row in territory_maps first.
     */
    protected function ensureTerritoryMapStub(string $slug, array $attributes = []): TerritoryMap
    {
        return TerritoryMap::query()->firstOrCreate(
            ['slug' => $slug],
            array_merge([
                'name' => $slug,
                'width_tiles' => 1,
                'height_tiles' => 1,
                'tile_size' => 32,
                'terrain_path' => null,
            ], $attributes)
        );
    }
}
