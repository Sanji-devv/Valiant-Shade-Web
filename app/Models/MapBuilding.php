<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapBuilding extends Model
{
    protected $fillable = [
        'territory_map_id',
        'building_type_id',
        'label',
        'grid_x',
        'grid_y',
        'rotation',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(TerritoryMap::class, 'territory_map_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class, 'building_type_id');
    }
}
