<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\MapPortal */
class MapPortalResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'target_map_slug' => $this->target_map_slug,
            'target_map_name' => $this->whenLoaded('targetMap', fn () => $this->targetMap?->name),
            'grid_x' => $this->grid_x,
            'grid_y' => $this->grid_y,
            'width_tiles' => $this->width_tiles,
            'height_tiles' => $this->height_tiles,
            'description' => $this->description,
        ];
    }
}
