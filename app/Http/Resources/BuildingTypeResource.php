<?php

namespace App\Http\Resources;

use App\Models\BuildingType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BuildingType */
class BuildingTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'width_tiles' => $this->width_tiles,
            'height_tiles' => $this->height_tiles,
            'icon' => $this->icon,
            'sprite_url' => $this->sprite_path ? asset('storage/'.$this->sprite_path) : null,
            'category' => $this->category,
            'is_unique' => $this->is_unique,
            'is_draggable' => $this->is_draggable,
        ];
    }
}
