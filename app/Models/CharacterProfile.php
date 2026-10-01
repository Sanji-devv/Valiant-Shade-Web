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
        'avatar_url',
        'title',
        'race',
        'class_name',
        'archetype',
        'level',
        'rank',
        'character_type',
        'bio',
        'bio_text',
        'notes',
        'rp_notes',
        'sheet_data',
        // Virtual attributes mapped into sheet_data
        'str', 'dex', 'con', 'int', 'wis', 'cha',
        'hp', 'max_hp', 'hit_die', 'bab',
        'fort_save', 'ref_save', 'will_save',
    ];

    protected $casts = [
        'level' => 'integer',
        'sheet_data' => 'array',
    ];

    protected $appends = [
        'str', 'dex', 'con', 'int', 'wis', 'cha',
        'hp', 'max_hp', 'hit_die', 'bab',
        'fort_save', 'ref_save', 'will_save',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }

    public function feats(): HasMany
    {
        return $this->hasMany(CharacterFeat::class, 'character_name', 'character_name')->orderBy('level')->orderBy('sort_order');
    }

    public function traits(): HasMany
    {
        return $this->hasMany(CharacterTrait::class, 'character_name', 'character_name')->orderBy('required_hierarchy');
    }

    /**
     * Store combat stats into sheet_data as character_info is the SSOT.
     */
    protected function setSheetAttribute(string $key, mixed $value): void
    {
        $sheet = $this->sheet_data;
        if (is_string($sheet)) {
            $sheet = json_decode($sheet, true);
        }
        if (!is_array($sheet)) {
            $sheet = [];
        }
        $sheet[$key] = $value;

        // Synchronize ability scores into abilityScores object
        if (in_array($key, ['str', 'dex', 'con', 'int', 'wis', 'cha'])) {
            if (!isset($sheet['abilityScores'])) {
                $sheet['abilityScores'] = [];
            }
            $sheet['abilityScores'][$key] = $value;

            if (isset($sheet['sheetData']) && is_array($sheet['sheetData'])) {
                if (!isset($sheet['sheetData']['abilityScores'])) {
                    $sheet['sheetData']['abilityScores'] = [];
                }
                $sheet['sheetData']['abilityScores'][$key] = $value;
                $sheet['sheetData'][$key] = $value;
            }
        } elseif (isset($sheet['sheetData']) && is_array($sheet['sheetData'])) {
            $sheet['sheetData'][$key] = $value;
        }

        $this->sheet_data = $sheet;
    }

    protected function getSheetAttribute(string $key, mixed $default = null): mixed
    {
        $sheet = $this->sheet_data;
        if (is_string($sheet)) {
            $sheet = json_decode($sheet, true);
        }
        if (!is_array($sheet)) {
            return $default;
        }

        if (array_key_exists($key, $sheet) && $sheet[$key] !== null) {
            return $sheet[$key];
        }

        // Check nested sheetData
        if (isset($sheet['sheetData']) && is_array($sheet['sheetData']) && array_key_exists($key, $sheet['sheetData'])) {
            return $sheet['sheetData'][$key];
        }

        // Check nested abilityScores
        if (isset($sheet['abilityScores']) && is_array($sheet['abilityScores']) && array_key_exists($key, $sheet['abilityScores'])) {
            return $sheet['abilityScores'][$key];
        }
        if (isset($sheet['sheetData']['abilityScores']) && is_array($sheet['sheetData']['abilityScores']) && array_key_exists($key, $sheet['sheetData']['abilityScores'])) {
            return $sheet['sheetData']['abilityScores'][$key];
        }

        return $default;
    }

    public function setStrAttribute($value): void { $this->setSheetAttribute('str', (int) $value); }
    public function getStrAttribute(): int { return (int) $this->getSheetAttribute('str', 10); }

    public function setDexAttribute($value): void { $this->setSheetAttribute('dex', (int) $value); }
    public function getDexAttribute(): int { return (int) $this->getSheetAttribute('dex', 10); }

    public function setConAttribute($value): void { $this->setSheetAttribute('con', (int) $value); }
    public function getConAttribute(): int { return (int) $this->getSheetAttribute('con', 10); }

    public function setIntAttribute($value): void { $this->setSheetAttribute('int', (int) $value); }
    public function getIntAttribute(): int { return (int) $this->getSheetAttribute('int', 10); }

    public function setWisAttribute($value): void { $this->setSheetAttribute('wis', (int) $value); }
    public function getWisAttribute(): int { return (int) $this->getSheetAttribute('wis', 10); }

    public function setChaAttribute($value): void { $this->setSheetAttribute('cha', (int) $value); }
    public function getChaAttribute(): int { return (int) $this->getSheetAttribute('cha', 10); }

    public function setHpAttribute($value): void
    {
        $sheet = $this->sheet_data;
        if (is_string($sheet)) $sheet = json_decode($sheet, true);
        if (!is_array($sheet)) $sheet = [];
        $val = (int) $value;
        $sheet['hp'] = $val;
        $sheet['currentHp'] = $val;
        if (isset($sheet['sheetData']) && is_array($sheet['sheetData'])) {
            $sheet['sheetData']['hp'] = $val;
            $sheet['sheetData']['currentHp'] = $val;
        }
        $this->sheet_data = $sheet;
    }

    public function getHpAttribute(): ?int
    {
        $sheet = $this->sheet_data;
        if (is_string($sheet)) $sheet = json_decode($sheet, true);
        if (is_array($sheet)) {
            if (isset($sheet['hp'])) return (int) $sheet['hp'];
            if (isset($sheet['currentHp'])) return (int) $sheet['currentHp'];
            if (isset($sheet['maxHp'])) return (int) $sheet['maxHp'];
            if (isset($sheet['max_hp'])) return (int) $sheet['max_hp'];
            if (isset($sheet['sheetData']['currentHp'])) return (int) $sheet['sheetData']['currentHp'];
            if (isset($sheet['sheetData']['hp'])) return (int) $sheet['sheetData']['hp'];
            if (isset($sheet['sheetData']['maxHp'])) return (int) $sheet['sheetData']['maxHp'];
        }
        return 10;
    }

    public function setMaxHpAttribute($value): void
    {
        $sheet = $this->sheet_data;
        if (is_string($sheet)) $sheet = json_decode($sheet, true);
        if (!is_array($sheet)) $sheet = [];
        $val = (int) $value;
        $sheet['max_hp'] = $val;
        $sheet['maxHp'] = $val;
        if (isset($sheet['sheetData']) && is_array($sheet['sheetData'])) {
            $sheet['sheetData']['max_hp'] = $val;
            $sheet['sheetData']['maxHp'] = $val;
        }
        $this->sheet_data = $sheet;
    }

    public function getMaxHpAttribute(): ?int
    {
        $sheet = $this->sheet_data;
        if (is_string($sheet)) $sheet = json_decode($sheet, true);
        if (is_array($sheet)) {
            if (isset($sheet['max_hp'])) return (int) $sheet['max_hp'];
            if (isset($sheet['maxHp'])) return (int) $sheet['maxHp'];
            if (isset($sheet['hp'])) return (int) $sheet['hp'];
            if (isset($sheet['currentHp'])) return (int) $sheet['currentHp'];
            if (isset($sheet['sheetData']['maxHp'])) return (int) $sheet['sheetData']['maxHp'];
            if (isset($sheet['sheetData']['currentHp'])) return (int) $sheet['sheetData']['currentHp'];
        }
        return 10;
    }

    public function setHitDieAttribute($value): void { $this->setSheetAttribute('hit_die', (string) $value); }
    public function getHitDieAttribute(): string { return (string) $this->getSheetAttribute('hit_die', 'd10'); }

    public function setBabAttribute($value): void { $this->setSheetAttribute('bab', (string) $value); }
    public function getBabAttribute(): ?string { return $this->getSheetAttribute('bab'); }

    public function setFortSaveAttribute($value): void { $this->setSheetAttribute('fort_save', (string) $value); }
    public function getFortSaveAttribute(): ?string { return $this->getSheetAttribute('fort_save'); }

    public function setRefSaveAttribute($value): void { $this->setSheetAttribute('ref_save', (string) $value); }
    public function getRefSaveAttribute(): ?string { return $this->getSheetAttribute('ref_save'); }

    public function setWillSaveAttribute($value): void { $this->setSheetAttribute('will_save', (string) $value); }
    public function getWillSaveAttribute(): ?string { return $this->getSheetAttribute('will_save'); }

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
