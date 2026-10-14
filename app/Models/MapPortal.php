<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapPortal extends Model
{
    protected $fillable = [
        'territory_map_id',
        'name',
        'target_map_slug',
        'grid_x',
        'grid_y',
        'width_tiles',
        'height_tiles',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(TerritoryMap::class, 'territory_map_id');
    }

    public function targetMap(): BelongsTo
    {
        return $this->belongsTo(TerritoryMap::class, 'target_map_slug', 'slug');
    }
}
