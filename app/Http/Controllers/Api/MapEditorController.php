<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MapBuildingResource;
use App\Models\BuildingType;
use App\Models\MapBuilding;
use App\Models\TerritoryMap;
use App\Services\Map\MapCollisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapEditorController extends Controller
{
    public function __construct(private MapCollisionService $collision) {}

    public function save(Request $request, string $slug): JsonResponse
    {
        $map = TerritoryMap::query()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'terrain_data' => ['required', 'array'],
            'terrain_data.*' => ['integer', 'min:0'],
            'buildings' => ['required', 'array'],
            'buildings.*.id' => ['nullable', 'integer'],
            'buildings.*.building_type_id' => ['required', 'integer', 'exists:building_types,id'],
            'buildings.*.grid_x' => ['required', 'integer', 'min:0'],
            'buildings.*.grid_y' => ['required', 'integer', 'min:0'],
            'buildings.*.label' => ['nullable', 'string', 'max:64'],
            'width_tiles' => ['required', 'integer', 'min:1', 'max:120'],
            'height_tiles' => ['required', 'integer', 'min:1', 'max:120'],
        ]);

        $expected = $validated['width_tiles'] * $validated['height_tiles'];
        if (count($validated['terrain_data']) !== $expected) {
            return response()->json(['message' => 'Arazi verisi boyutla uyuşmuyor.'], 422);
        }

        DB::transaction(function () use ($map, $validated): void {
            $map->update([
                'width_tiles' => $validated['width_tiles'],
                'height_tiles' => $validated['height_tiles'],
                'terrain_data' => $validated['terrain_data'],
            ]);

            $keptIds = [];

            foreach ($validated['buildings'] as $item) {
                $type = BuildingType::query()->findOrFail($item['building_type_id']);

                if (! empty($item['id'])) {
                    $building = MapBuilding::query()
                        ->where('territory_map_id', $map->id)
                        ->where('id', $item['id'])
                        ->firstOrFail();

                    $building->update([
                        'building_type_id' => $type->id,
                        'grid_x' => $item['grid_x'],
                        'grid_y' => $item['grid_y'],
                        'label' => $item['label'] ?? null,
                        'is_active' => true,
                    ]);
                    $keptIds[] = $building->id;
                    continue;
                }

                if ($type->is_unique) {
                    $existing = MapBuilding::query()
                        ->where('territory_map_id', $map->id)
                        ->where('building_type_id', $type->id)
                        ->where('is_active', true)
                        ->first();
                    if ($existing) {
                        $existing->update([
                            'grid_x' => $item['grid_x'],
                            'grid_y' => $item['grid_y'],
                            'label' => $item['label'] ?? null,
                        ]);
                        $keptIds[] = $existing->id;
                        continue;
                    }
                }

                $building = MapBuilding::query()->create([
                    'territory_map_id' => $map->id,
                    'building_type_id' => $type->id,
                    'grid_x' => $item['grid_x'],
                    'grid_y' => $item['grid_y'],
                    'label' => $item['label'] ?? null,
                    'is_active' => true,
                ]);
                $keptIds[] = $building->id;
            }

            MapBuilding::query()
                ->where('territory_map_id', $map->id)
                ->whereNotIn('id', $keptIds)
                ->update(['is_active' => false]);
        });

        $map->refresh()->load(['buildings.type']);

        return response()->json([
            'map' => [
                'width_tiles' => $map->width_tiles,
                'height_tiles' => $map->height_tiles,
            ],
            'terrain_data' => $map->terrain_data,
            'buildings' => MapBuildingResource::collection($map->buildings->where('is_active', true)->values()),
        ]);
    }
}
