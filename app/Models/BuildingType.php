<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuildingType extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'width_tiles',
        'height_tiles',
        'icon',
        'sprite_path',
        'category',
        'is_unique',
        'is_draggable',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_unique' => 'boolean',
            'is_draggable' => 'boolean',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function placements(): HasMany
    {
        return $this->hasMany(MapBuilding::class);
    }
}
