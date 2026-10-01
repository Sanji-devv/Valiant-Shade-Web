<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feat extends Model
{
    use HasFactory;

    protected $table = 'feats';

    protected $fillable = [
        'name',
        'category',
        'prerequisites',
        'benefit',
        'normal_effect',
        'special',
    ];

    public const CATEGORY_COMBAT = 'COMBAT';
    public const CATEGORY_GENERAL = 'GENERAL';
    public const CATEGORY_METAMAGIC = 'METAMAGIC';
    public const CATEGORY_ITEM_CREATION = 'ITEM_CREATION';
    public const CATEGORY_RACIAL = 'RACIAL';

    public function characterFeats(): HasMany
    {
        return $this->hasMany(CharacterFeat::class, 'feat_id');
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'character_feats', 'feat_id', 'character_id')
            ->withPivot(['custom_feat_name', 'acquired_at_level', 'slot_type', 'notes'])
            ->withTimestamps();
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', strtoupper($category));
    }

    public function scopeCombat($query)
    {
        return $query->where('category', self::CATEGORY_COMBAT);
    }
}
