<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CharacterFeat extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'character_feats';

    protected $fillable = [
        'character_id',
        'feat_id',
        'custom_feat_name',
        'acquired_at_level',
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
        'notes',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'feat_id' => 'integer',
        'acquired_at_level' => 'integer',
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

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }

    public function feat(): BelongsTo
    {
        return $this->belongsTo(Feat::class, 'feat_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CharacterProfile::class, 'character_name', 'character_name');
    }

    public function getAcquiredAtLevelAttribute(?int $value): int
    {
        return $value ?? (int) ($this->attributes['level'] ?? 1);
    }

    public function setLevelAttribute($value): void
    {
        $this->attributes['level'] = (int) $value;
        if (!isset($this->attributes['acquired_at_level'])) {
            $this->attributes['acquired_at_level'] = (int) $value;
        }
    }
}
