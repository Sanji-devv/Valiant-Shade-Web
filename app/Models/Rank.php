<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rank extends Model
{
    use HasFactory;

    protected $table = 'ranks';

    protected $fillable = [
        'name',
        'base_floor_xp',
        'hierarchy_order',
    ];

    protected $casts = [
        'base_floor_xp' => 'float',
        'hierarchy_order' => 'integer',
    ];

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class, 'rank_id');
    }
}
