<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapTileType extends Model
{
    protected $fillable = [
        'territory_map_id',
        'slug',
        'name',
        'fill_color',
        'stroke_color',
        'sort_order',
    ];

    public function map(): BelongsTo
    {
        return $this->belongsTo(TerritoryMap::class, 'territory_map_id');
    }
}
