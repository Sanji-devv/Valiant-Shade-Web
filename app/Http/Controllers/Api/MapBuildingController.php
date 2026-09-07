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

class MapBuildingController extends Controller
{
    public function __construct(private MapCollisionService $collision) {}

    public function store(Request $request, string $slug): JsonResponse
    {
        $map = TerritoryMap::query()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'building_type_id' => ['required', 'integer', 'exists:building_types,id'],
            'grid_x' => ['required', 'integer', 'min:0'],
            'grid_y' => ['required', 'integer', 'min:0'],
            'label' => ['nullable', 'string', 'max:64'],
        ]);

        $type = BuildingType::query()->findOrFail($validated['building_type_id']);

        if ($type->is_unique) {
            $exists = MapBuilding::query()
                ->where('territory_map_id', $map->id)
                ->where('building_type_id', $type->id)
                ->where('is_active', true)
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Bu yapı türünden yalnızca bir tane olabilir.'], 422);
            }
        }

        $siblings = MapBuilding::query()
            ->where('territory_map_id', $map->id)
            ->where('is_active', true)
            ->with('type')
            ->get();

        if (! $this->collision->canPlace(
            $siblings,
            $map->width_tiles,
            $map->height_tiles,
            $validated['grid_x'],
            $validated['grid_y'],
            $type->width_tiles,
            $type->height_tiles,
        )) {
            return response()->json(['message' => 'Bu konuma yerleştirilemez.'], 422);
        }

        $building = MapBuilding::query()->create([
            'territory_map_id' => $map->id,
            'building_type_id' => $type->id,
            'label' => $validated['label'] ?? null,
            'grid_x' => $validated['grid_x'],
            'grid_y' => $validated['grid_y'],
            'is_active' => true,
        ]);

        $building->load('type');

        return response()->json(['building' => new MapBuildingResource($building)], 201);
    }

    public function update(Request $request, MapBuilding $mapBuilding): JsonResponse
    {
        $validated = $request->validate([
            'grid_x' => ['required', 'integer', 'min:0'],
            'grid_y' => ['required', 'integer', 'min:0'],
        ]);

        $mapBuilding->load(['map', 'type']);
        $type = $mapBuilding->type;

        if (! $type->is_draggable) {
            return response()->json(['message' => 'Bu yapı taşınamaz.'], 422);
        }

        $siblings = MapBuilding::query()
            ->where('territory_map_id', $mapBuilding->territory_map_id)
            ->where('is_active', true)
            ->with('type')
            ->get();

        if (! $this->collision->canPlace(
            $siblings,
            $mapBuilding->map->width_tiles,
            $mapBuilding->map->height_tiles,
            $validated['grid_x'],
            $validated['grid_y'],
            $type->width_tiles,
            $type->height_tiles,
            $mapBuilding->id,
        )) {
            return response()->json(['message' => 'Bu konuma yerleştirilemez (sınır veya çakışma).'], 422);
        }

        $mapBuilding->update($validated);

        return response()->json([
            'building' => new MapBuildingResource($mapBuilding->fresh('type')),
        ]);
    }

    public function destroy(MapBuilding $mapBuilding): JsonResponse
    {
        $mapBuilding->update(['is_active' => false]);

        return response()->json(['ok' => true]);
    }
}
