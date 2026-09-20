<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharacterProfile extends Model
{
    use HasFactory;

    protected $table = 'character_profiles';

    protected $fillable = [
        'character_id',
        'user_id',
        'character_name',
        'player_name',
        'race',
        'class_name',
        'archetype',
        'level',
        'rank',
        'character_type',
        'str',
        'dex',
        'con',
        'int',
        'wis',
        'cha',
        'hp',
        'max_hp',
        'hit_die',
        'bab',
        'fort_save',
        'ref_save',
        'will_save',
        'bio',
        'notes',
        'sheet_data',
    ];

    protected $casts = [
        'level' => 'integer',
        'str' => 'integer',
        'dex' => 'integer',
        'con' => 'integer',
        'int' => 'integer',
        'wis' => 'integer',
        'cha' => 'integer',
        'hp' => 'integer',
        'max_hp' => 'integer',
        'sheet_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function feats(): HasMany
    {
        return $this->hasMany(CharacterFeat::class, 'character_name', 'character_name')->orderBy('level')->orderBy('sort_order');
    }

    /**
     * Get attribute modifier (e.g. 14 -> +2, 8 -> -1).
     */
    public function getMod(string $attr): int
    {
        $val = $this->{$attr} ?? 10;
        return (int) floor(($val - 10) / 2);
    }

    public function getModStr(string $attr): string
    {
        $mod = $this->getMod($attr);
        return ($mod >= 0 ? '+' : '') . $mod;
    }

    public function isMain(): bool
    {
        return strtolower($this->rank ?? '') === 'main' || strtolower($this->character_type ?? '') === 'main';
    }

    public function isClerk(): bool
    {
        return strtolower($this->rank ?? '') === 'clerk' || strtolower($this->character_type ?? '') === 'clerk';
    }
}
