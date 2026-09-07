<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TerritoryMap extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'width_tiles',
        'height_tiles',
        'tile_size',
        'terrain_path',
        'terrain_data',
    ];

    protected function casts(): array
    {
        return [
            'terrain_data' => 'array',
        ];
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(MapBuilding::class);
    }

    public function tileTypes(): HasMany
    {
        return $this->hasMany(MapTileType::class);
    }
}
