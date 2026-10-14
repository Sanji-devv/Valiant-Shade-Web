<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CharacterXp extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'character_xp';

    protected $fillable = [
        'character_id',
        'current_level',
        'total_xp',
        'quest_xp',
        'report_xp',
        'bailiff_xp',
        'ex_char_xp',
        'wiki_xp',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'current_level' => 'integer',
        'total_xp' => 'decimal:2',
        'quest_xp' => 'decimal:2',
        'report_xp' => 'decimal:2',
        'bailiff_xp' => 'decimal:2',
        'ex_char_xp' => 'decimal:2',
        'wiki_xp' => 'decimal:2',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }
}
