<?php

namespace App\Http\Resources;

use App\Models\MapBuilding;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MapBuilding */
class MapBuildingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'grid_x' => $this->grid_x,
            'grid_y' => $this->grid_y,
            'rotation' => $this->rotation,
            'type' => [
                'id' => $this->type->id,
                'slug' => $this->type->slug,
                'name' => $this->type->name,
                'description' => $this->type->description,
                'width_tiles' => $this->type->width_tiles,
                'height_tiles' => $this->type->height_tiles,
                'icon' => $this->type->icon,
                'sprite_url' => $this->type->sprite_path ? asset('storage/'.$this->type->sprite_path) : null,
                'category' => $this->type->category,
                'is_draggable' => $this->type->is_draggable,
            ],
        ];
    }
}
