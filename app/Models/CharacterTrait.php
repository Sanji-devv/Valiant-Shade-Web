<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CharacterTrait extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'character_traits';

    protected $fillable = [
        'character_id',
        'trait_id',
        'character_name',
        'slot_key',
        'slot_name',
        'required_rank',
        'required_hierarchy',
        'trait_name',
        'category',
        'description',
        'source_url',
        'notes',
        'rank',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'trait_id' => 'integer',
        'required_hierarchy' => 'integer',
        'rank' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }

    public function traitDefinition(): BelongsTo
    {
        return $this->belongsTo(PathfinderTrait::class, 'trait_id');
    }
}