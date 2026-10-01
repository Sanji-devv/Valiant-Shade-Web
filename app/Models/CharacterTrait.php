<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharacterTrait extends Model
{
    use HasFactory;

    protected $table = 'character_traits';

    protected $fillable = [
        'character_id',
        'character_name',
        'slot_key',
        'slot_name',
        'required_rank',
        'required_hierarchy',
        'trait_name',
        'category',
        'description',
        'source_url',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}