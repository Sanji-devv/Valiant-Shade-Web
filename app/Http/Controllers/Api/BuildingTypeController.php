<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuildingTypeResource;
use App\Models\BuildingType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BuildingTypeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'slug' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:500'],
            'width_tiles' => ['required', 'integer', 'min:1', 'max:20'],
            'height_tiles' => ['required', 'integer', 'min:1', 'max:20'],
            'icon' => ['nullable', 'string', 'max:16'],
            'category' => ['nullable', 'string', 'max:32'],
            'is_unique' => ['boolean'],
        ]);

        $slug = $validated['slug'] ?? str($validated['name'])->slug()->toString();
        $base = $slug;
        $i = 1;
        while (BuildingType::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        $type = BuildingType::query()->create([
            'slug' => $slug,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'width_tiles' => $validated['width_tiles'],
            'height_tiles' => $validated['height_tiles'],
            'icon' => $validated['icon'] ?? '🏠',
            'category' => $validated['category'] ?? 'utility',
            'is_unique' => $validated['is_unique'] ?? false,
            'is_draggable' => true,
            'is_active' => true,
        ]);

        return response()->json(['building_type' => new BuildingTypeResource($type)], 201);
    }

    public function uploadSprite(Request $request, BuildingType $buildingType): JsonResponse
    {
        $request->validate([
            'sprite' => ['required', 'image', 'max:2048'],
        ]);

        if ($buildingType->sprite_path) {
            Storage::disk('public')->delete($buildingType->sprite_path);
        }

        $path = $request->file('sprite')->store('map/buildings', 'public');
        $buildingType->update(['sprite_path' => $path]);

        return response()->json([
            'building_type' => new BuildingTypeResource($buildingType->fresh()),
        ]);
    }

    public function clearSprite(BuildingType $buildingType): JsonResponse
    {
        if ($buildingType->sprite_path) {
            Storage::disk('public')->delete($buildingType->sprite_path);
            $buildingType->update(['sprite_path' => null]);
        }

        return response()->json([
            'building_type' => new BuildingTypeResource($buildingType->fresh()),
        ]);
    }
}
