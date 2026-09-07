<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuildingTypeResource;
use App\Http\Resources\MapBuildingResource;
use App\Models\BuildingType;
use App\Models\TerritoryMap;
use App\Services\Map\MapBootstrapService;
use App\Services\Map\TerrainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

class TerritoryMapController extends Controller
{
    public function __construct(
        private TerrainService $terrain,
        private MapBootstrapService $bootstrap,
    ) {}

    public function show(string $slug): JsonResponse
    {
        $map = TerritoryMap::query()
            ->where('slug', $slug)
            ->with(['buildings.type'])
            ->firstOrFail();

        $tileTypes = $this->bootstrap->ensureTileTypes($map);

        $terrainData = $map->terrain_data;
        if (! is_array($terrainData) || count($terrainData) !== $map->width_tiles * $map->height_tiles) {
            $terrainData = $this->loadTerrainFromFile($map);
            if ($terrainData === null) {
                $terrainData = array_fill(0, $map->width_tiles * $map->height_tiles, $tileTypes->first()->id);
            }
        }

        $terrainData = $this->bootstrap->normalizeTerrainData($terrainData, $tileTypes);
        $map->update(['terrain_data' => $terrainData]);

        $buildingTypes = BuildingType::query()->where('is_active', true)->orderBy('name')->get();

        return response()->json([
            'map' => [
                'id' => $map->id,
                'slug' => $map->slug,
                'name' => $map->name,
                'width_tiles' => $map->width_tiles,
                'height_tiles' => $map->height_tiles,
                'tile_size' => $map->tile_size,
            ],
            'tile_types' => $tileTypes->map(fn ($type) => [
                'id' => $type->id,
                'slug' => $type->slug,
                'name' => $type->name,
                'fill_color' => $type->fill_color,
                'stroke_color' => $type->stroke_color,
                'sort_order' => $type->sort_order,
            ])->values(),
            'building_types' => BuildingTypeResource::collection($buildingTypes),
            'terrain_data' => $terrainData,
            'buildings' => MapBuildingResource::collection(
                $map->buildings->where('is_active', true)->values(),
            ),
        ]);
    }

    /** @return array<int,int>|null */
    private function loadTerrainFromFile(TerritoryMap $map): ?array
    {
        if (! $map->terrain_path) {
            return null;
        }

        $path = public_path($map->terrain_path);
        if (! File::exists($path)) {
            return null;
        }

        $tiled = json_decode(File::get($path), true);
        $layer = collect($tiled['layers'] ?? [])->first(
            fn ($item) => ($item['type'] ?? '') === 'tilelayer' && ($item['name'] ?? '') === 'ground',
        );

        return $layer['data'] ?? null;
    }
}
