<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterFeat extends Model
{
    use HasFactory;

    protected $table = 'character_feats';

    protected $fillable = [
        'character_name',
        'level',
        'slot_type',
        'feat_name',
        'class_feat_name',
        'category',
        'prerequisites',
        'notes',
        'is_class_feat',
        'sort_order',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_class_feat' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const SLOT_STANDARD = 'standard';         // Standard feat (1, 3, 5, 7, 9, 11, 13, 15, 17, 19)
    public const SLOT_RACIAL_BONUS = 'racial_bonus'; // e.g. Human 1st level bonus feat
    public const SLOT_CLASS_BONUS = 'class_bonus';   // Fighter, Wizard, Monk bonus feats
    public const SLOT_CLASS_TALENT = 'class_talent'; // Rogue Talent, Magus Arcana, Discovery, Hex, etc.
    public const SLOT_TRAIT = 'trait';               // Additional Traits or character traits
    public const SLOT_RESERVE = 'reserve';           // Reserve / planned feats (Yedek Feat)

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CharacterProfile::class, 'character_name', 'character_name');
    }
}
