<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MapTileType;
use App\Models\TerritoryMap;
use App\Services\Map\TerrainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapTerrainController extends Controller
{
    public function __construct(private TerrainService $terrain) {}

    public function paint(Request $request, string $slug): JsonResponse
    {
        $this->assertEditor($request);

        $validated = $request->validate([
            'tiles' => ['required', 'array', 'min:1', 'max:500'],
            'tiles.*.x' => ['required', 'integer', 'min:0'],
            'tiles.*.y' => ['required', 'integer', 'min:0'],
            'tiles.*.tile_type_id' => ['required', 'integer'],
        ]);

        $map = TerritoryMap::query()->where('slug', $slug)->firstOrFail();
        $data = $this->terrain->paintTiles($map, $validated['tiles']);

        return response()->json(['terrain_data' => $data]);
    }

    public function resize(Request $request, string $slug): JsonResponse
    {
        $this->assertEditor($request);

        $map = TerritoryMap::query()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'width_tiles' => ['required', 'integer', 'min:'.$map->width_tiles, 'max:120'],
            'height_tiles' => ['required', 'integer', 'min:'.$map->height_tiles, 'max:120'],
        ]);

        $defaultTile = MapTileType::query()
            ->where('territory_map_id', $map->id)
            ->orderBy('sort_order')
            ->value('id') ?? 1;

        $data = $this->terrain->resize($map, $validated['width_tiles'], $validated['height_tiles'], $defaultTile);
        $map->refresh();

        return response()->json([
            'map' => [
                'width_tiles' => $map->width_tiles,
                'height_tiles' => $map->height_tiles,
            ],
            'terrain_data' => $data,
        ]);
    }

    public function storeTileType(Request $request, string $slug): JsonResponse
    {
        $this->assertEditor($request);

        $map = TerritoryMap::query()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'fill_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'stroke_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $slugBase = str($validated['name'])->slug()->toString();
        $uniqueSlug = $slugBase;
        $counter = 1;
        while (MapTileType::query()->where('territory_map_id', $map->id)->where('slug', $uniqueSlug)->exists()) {
            $uniqueSlug = $slugBase.'-'.$counter;
            $counter++;
        }

        $tileType = MapTileType::query()->create([
            'territory_map_id' => $map->id,
            'slug' => $uniqueSlug,
            'name' => $validated['name'],
            'fill_color' => $validated['fill_color'],
            'stroke_color' => $validated['stroke_color'],
            'sort_order' => MapTileType::query()->where('territory_map_id', $map->id)->count(),
        ]);

        return response()->json(['tile_type' => $this->formatTileType($tileType)], 201);
    }

    public function updateTileType(Request $request, MapTileType $mapTileType): JsonResponse
    {
        $this->assertEditor($request);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:64'],
            'fill_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'stroke_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $mapTileType->update($validated);

        return response()->json(['tile_type' => $this->formatTileType($mapTileType->fresh())]);
    }

    private function assertEditor(Request $request): void
    {
        if (! $request->user()?->is_admin) {
            abort(403, 'Harita düzenleme yetkisi gerekli.');
        }
    }

    /** @return array<string,mixed> */
    private function formatTileType(MapTileType $type): array
    {
        return [
            'id' => $type->id,
            'slug' => $type->slug,
            'name' => $type->name,
            'fill_color' => $type->fill_color,
            'stroke_color' => $type->stroke_color,
            'sort_order' => $type->sort_order,
        ];
    }
}
