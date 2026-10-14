<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rank extends BaseModel
{
    use HasFactory, SoftDeletes;

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
