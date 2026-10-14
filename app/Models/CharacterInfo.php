<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CharacterInfo extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'character_info';

    protected $fillable = [
        'character_id',
        'class_and_level',
        'race',
        'alignment',
        'deity',
        'size',
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
        'str_mod',
        'dex_mod',
        'con_mod',
        'int_mod',
        'wis_mod',
        'cha_mod',
        'temp_ability_mods',
        'hp_max',
        'hp_current',
        'temp_hp',
        'nonlethal_damage',
        'armor_class_total',
        'touch_ac',
        'flat_footed_ac',
        'ac_breakdown',
        'damage_reduction',
        'spell_resistance',
        'fortitude_save',
        'reflex_save',
        'will_save',
        'base_attack_bonus',
        'cmb',
        'cmd',
        'initiative_mod',
        'speed_ft',
        'melee_attacks',
        'ranged_attacks',
        'special_attacks',
        'equipped_gear',
        'inventory_items',
        'total_carried_weight_lbs',
        'load_capacity',
        'pocket_money',
        'currency',
        'skills',
        'feats_and_traits',
        'spellbook_or_prepared',
    ];

    protected $casts = [
        'strength' => 'integer',
        'dexterity' => 'integer',
        'constitution' => 'integer',
        'intelligence' => 'integer',
        'wisdom' => 'integer',
        'charisma' => 'integer',
        'str_mod' => 'integer',
        'dex_mod' => 'integer',
        'con_mod' => 'integer',
        'int_mod' => 'integer',
        'wis_mod' => 'integer',
        'cha_mod' => 'integer',
        'temp_ability_mods' => 'array',
        'hp_max' => 'integer',
        'hp_current' => 'integer',
        'temp_hp' => 'integer',
        'nonlethal_damage' => 'integer',
        'armor_class_total' => 'integer',
        'touch_ac' => 'integer',
        'flat_footed_ac' => 'integer',
        'ac_breakdown' => 'array',
        'spell_resistance' => 'integer',
        'fortitude_save' => 'integer',
        'reflex_save' => 'integer',
        'will_save' => 'integer',
        'base_attack_bonus' => 'integer',
        'cmb' => 'integer',
        'cmd' => 'integer',
        'initiative_mod' => 'integer',
        'speed_ft' => 'integer',
        'melee_attacks' => 'array',
        'ranged_attacks' => 'array',
        'equipped_gear' => 'array',
        'inventory_items' => 'array',
        'total_carried_weight_lbs' => 'float',
        'pocket_money' => 'array',
        'currency' => 'array',
        'skills' => 'array',
        'feats_and_traits' => 'array',
        'spellbook_or_prepared' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (CharacterInfo $info) {
            // Automatically calculate PF1e ability modifiers
            $info->str_mod = (int) floor(((int) $info->strength - 10) / 2);
            $info->dex_mod = (int) floor(((int) $info->dexterity - 10) / 2);
            $info->con_mod = (int) floor(((int) $info->constitution - 10) / 2);
            $info->int_mod = (int) floor(((int) $info->intelligence - 10) / 2);
            $info->wis_mod = (int) floor(((int) $info->wisdom - 10) / 2);
            $info->cha_mod = (int) floor(((int) $info->charisma - 10) / 2);

            // Sync pocket_money and currency
            if (!empty($info->pocket_money) && empty($info->currency)) {
                $info->currency = $info->pocket_money;
            } elseif (!empty($info->currency) && empty($info->pocket_money)) {
                $info->pocket_money = $info->currency;
            }
        });
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }
}