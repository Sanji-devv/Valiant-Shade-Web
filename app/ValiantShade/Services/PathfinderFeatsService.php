<?php

namespace App\ValiantShade\Services;

use App\Models\CharacterFeat;
use App\Models\CharacterProfile;
use App\Models\PlayerXp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PathfinderFeatsService
{
    /**
     * Standard Pathfinder 1E feat level intervals: odd levels 1, 3, 5, 7, 9, 11, 13, 15, 17, 19.
     */
    public const STANDARD_FEAT_LEVELS = [1, 3, 5, 7, 9, 11, 13, 15, 17, 19];

    /**
     * Class-specific talent and bonus feat progressions in Pathfinder 1E.
     */
    public const CLASS_SPECIAL_SLOTS = [
        'fighter' => [
            'class_bonus' => [1, 2, 4, 6, 8, 10, 12, 14, 16, 18, 20], // Bonus Combat Feats
            'label' => 'Bonus Combat Feat',
        ],
        'rogue' => [
            'class_talent' => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20], // Rogue Talents
            'label' => 'Rogue Talent',
        ],
        'magus' => [
            'class_talent' => [3, 6, 9, 12, 15, 18], // Magus Arcana
            'class_bonus'  => [5, 11, 17],           // Bonus Combat/Metamagic Feat
            'label' => 'Magus Arcana / Bonus Feat',
        ],
        'ranger' => [
            'class_bonus' => [2, 6, 10, 14, 18],     // Combat Style Feats
            'label' => 'Combat Style Feat',
        ],
        'wizard' => [
            'class_bonus' => [1, 5, 10, 15, 20],     // Scribe Scroll (1), Metamagic/Item Feats
            'label' => 'Wizard Bonus Feat',
        ],
        'monk' => [
            'class_bonus' => [1, 2, 6, 10, 14, 18],  // Monk Bonus Feats
            'label' => 'Monk Bonus Feat',
        ],
        'alchemist' => [
            'class_talent' => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20], // Discoveries
            'label' => 'Discovery',
        ],
        'warpriest' => [
            'class_bonus' => [1, 3, 6, 9, 12, 15, 18], // Weapon Focus (1), Combat Feats
            'label' => 'Combat Feat / Blessing',
        ],
        'witch' => [
            'class_talent' => [1, 2, 4, 6, 8, 10, 12, 14, 16, 18, 20], // Hexes
            'label' => 'Hex',
        ],
        'slayer' => [
            'class_talent' => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20], // Slayer Talents
            'label' => 'Slayer Talent',
        ],
        'barbarian' => [
            'class_talent' => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20], // Rage Powers
            'label' => 'Rage Power',
        ],
        'investigator' => [
            'class_talent' => [3, 5, 7, 9, 11, 13, 15, 17, 19], // Investigator Talents
            'label' => 'Investigator Talent',
        ],
        'arcanist' => [
            'class_talent' => [1, 3, 5, 7, 9, 11, 13, 15, 17, 19], // Arcanist Exploits
            'label' => 'Arcanist Exploit',
        ],
        'inquisitor' => [
            'class_bonus' => [3, 6, 9, 12, 15, 18], // Teamwork Feats
            'label' => 'Teamwork Feat',
        ],
        'paladin' => [
            'class_talent' => [3, 6, 9, 12, 15, 18], // Mercies
            'label' => 'Mercy',
        ],
        'bloodrager' => [
            'class_bonus' => [6, 9, 12, 15, 18], // Bloodline Feats
            'label' => 'Bloodline Feat',
        ],
        'sorcerer' => [
            'class_bonus' => [7, 13, 19], // Bloodline Feats
            'label' => 'Bloodline Feat',
        ],
    ];

    /**
     * Ensure default character profiles and feats exist in DB.
     * Restricted strictly to testing environment so demo characters are never seeded in production/local.
     */
    public function ensureInitialized(): void
    {
        if (app()->environment('testing')) {
            if (CharacterProfile::count() === 0 || CharacterFeat::count() === 0) {
                $this->seedDefaults();
            }
        }
    }

    /**
     * Get all character profiles with summary count of feats, supporting sorting.
     */
    public function getCharactersList(string $sortBy = 'name_asc')
    {
        $this->ensureInitialized();

        $query = CharacterProfile::withCount('feats');

        if (Auth::check() && !app()->environment('testing')) {
            $query->where('user_id', Auth::id());
        }

        switch ($sortBy) {
            case 'name_desc':
                $query->orderBy('character_name', 'desc');
                break;
            case 'level_desc':
                $query->orderBy('level', 'desc')->orderBy('character_name', 'asc');
                break;
            case 'level_asc':
                $query->orderBy('level', 'asc')->orderBy('character_name', 'asc');
                break;
            case 'feats_desc':
                $query->orderBy('feats_count', 'desc')->orderBy('character_name', 'asc');
                break;
            case 'feats_asc':
                $query->orderBy('feats_count', 'asc')->orderBy('character_name', 'asc');
                break;
            case 'name_asc':
            default:
                $query->orderBy('character_name', 'asc');
                break;
        }

        return $query->get();
    }

    /**
     * Get character dossier (Orange page view matching Image 2).
     */
    public function getCharacterDossier(string $characterName)
    {
        $this->ensureInitialized();

        $query = CharacterProfile::query();
        if (Auth::check() && !app()->environment('testing')) {
            $query->where('user_id', Auth::id());
        }

        $profile = (clone $query)->where('character_name', $characterName)->first();

        if (!$profile) {
            $profile = (clone $query)->first();
        }

        if (!$profile) {
            return null;
        }

        // Fetch all feats for this character
        $feats = CharacterFeat::where('character_name', $profile->character_name)
            ->orderBy('level')
            ->orderBy('sort_order')
            ->get();

        // Calculate BAB and Saves based on Class & Level
        $progressionStats = $this->calculateProgressionStats($profile->class_name, $profile->level, [
            'str' => $profile->str,
            'dex' => $profile->dex,
            'con' => $profile->con,
            'int' => $profile->int,
            'wis' => $profile->wis,
            'cha' => $profile->cha,
        ]);

        // Merge calculated stats if not manually overridden
        $calculatedBab = $profile->bab ?: $progressionStats['bab'];
        $calculatedFort = $profile->fort_save ?: $progressionStats['fort'];
        $calculatedRef = $profile->ref_save ?: $progressionStats['ref'];
        $calculatedWill = $profile->will_save ?: $progressionStats['will'];
        $hitDie = $profile->hit_die ?: $progressionStats['hit_die'];

        // Group feats by level (1 to 20)
        $levelProgression = [];
        $classSlug = strtolower(explode(' ', trim(explode('/', $profile->class_name)[0]))[0]);
        $classRules = self::CLASS_SPECIAL_SLOTS[$classSlug] ?? [];

        for ($lvl = 1; $lvl <= 20; $lvl++) {
            $levelFeats = $feats->where('level', $lvl);

            $isStandardFeatLevel = in_array($lvl, self::STANDARD_FEAT_LEVELS);
            $hasClassBonusSlot = !empty($classRules['class_bonus']) && in_array($lvl, $classRules['class_bonus']);
            $hasClassTalentSlot = !empty($classRules['class_talent']) && in_array($lvl, $classRules['class_talent']);
            $isRacialSlot = ($lvl === 1 && str_contains(strtolower($profile->race), 'human'));

            // Filter feats into columns:
            // 1. All active feats: Standard, Racial, Traits, Class Talents, AND Class Bonus Feats (as requested: Sınıf yetenekleri ve bonus featleri Feat Adı kısmına dahil)
            $allActiveFeats = $levelFeats->filter(function ($f) {
                return $f->slot_type !== 'reserve';
            });

            $standardAndTraitFeats = $levelFeats->filter(function ($f) {
                return in_array($f->slot_type, ['standard', 'racial_bonus', 'trait']) && !$f->is_class_feat;
            });

            $classFeatsAndTalents = $levelFeats->filter(function ($f) {
                return in_array($f->slot_type, ['class_bonus', 'class_talent']) || $f->is_class_feat;
            });

            $reserveAndNotes = $levelFeats->filter(function ($f) {
                return $f->slot_type === 'reserve';
            });

            $levelProgression[$lvl] = [
                'level' => $lvl,
                'is_current_level' => ($lvl <= $profile->level),
                'is_standard_feat_level' => $isStandardFeatLevel,
                'has_class_bonus_slot' => $hasClassBonusSlot,
                'has_class_talent_slot' => $hasClassTalentSlot,
                'is_racial_slot' => $isRacialSlot,
                'class_slot_label' => $classRules['label'] ?? 'Class Ability',
                'all_feats' => $levelFeats->values(),
                'all_active_feats' => $allActiveFeats->values(),
                'standard_feats' => $standardAndTraitFeats->values(),
                'class_feats' => $classFeatsAndTalents->values(),
                'reserve_feats' => $reserveAndNotes->values(),
                // Display text strings formatted like Image 2:
                'feat_name_display' => $allActiveFeats->pluck('feat_name')->filter()->join(', ') ?: '-',
                'class_feat_display' => $classFeatsAndTalents->pluck('feat_name')->filter()->join(', ') ?: '-',
                'reserve_display' => $reserveAndNotes->pluck('feat_name')->filter()->join(', ') ?: '-',
            ];
        }

        return [
            'profile' => $profile,
            'calculated_bab' => $calculatedBab,
            'calculated_fort' => $calculatedFort,
            'calculated_ref' => $calculatedRef,
            'calculated_will' => $calculatedWill,
            'hit_die' => $hitDie,
            'progression' => $levelProgression,
            'total_feats_count' => $feats->count(),
            'standard_count' => $feats->where('slot_type', 'standard')->count(),
            'class_count' => $feats->whereIn('slot_type', ['class_bonus', 'class_talent'])->count(),
            'racial_count' => $feats->where('slot_type', 'racial_bonus')->count(),
        ];
    }

    /**
     * Get Master Feats Table dataset matching Google Sheet Image 1, supporting sorting.
     */
    public function getMasterFeatsTable(string $sortBy = 'name_asc')
    {
        $this->ensureInitialized();

        $profiles = $this->getCharactersList($sortBy);
        $allFeats = CharacterFeat::all()->groupBy('character_name');

        $rows = [];
        foreach ($profiles as $p) {
            $charFeats = $allFeats->get($p->character_name, collect());

            // 1. Level Feat (Standard / Traits)
            $lvl1Standard = $charFeats->filter(function ($f) {
                return $f->level === 1 && in_array($f->slot_type, ['standard', 'trait']);
            })->pluck('feat_name')->join(', ');

            // 1. Level Class Bonus Feat
            $lvl1ClassBonus = $charFeats->filter(function ($f) {
                return $f->level === 1 && in_array($f->slot_type, ['class_bonus', 'class_talent']);
            })->pluck('feat_name')->join(', ');

            // 1. Level Racial Bonus Feat
            $lvl1RacialBonus = $charFeats->filter(function ($f) {
                return $f->level === 1 && $f->slot_type === 'racial_bonus';
            })->pluck('feat_name')->join(', ');

            // 2. Level Class Bonus Feat/Ability
            $lvl2ClassBonus = $charFeats->filter(function ($f) {
                return $f->level === 2 && in_array($f->slot_type, ['class_bonus', 'class_talent']);
            })->pluck('feat_name')->join(', ');

            // 3. Level Feat (Standard & Class Talents like Magus Arcana)
            $lvl3Standard = $charFeats->filter(function ($f) {
                return $f->level === 3 && in_array($f->slot_type, ['standard', 'trait', 'class_talent']);
            })->pluck('feat_name')->join(', ');

            // All other feats summary
            $higherFeats = $charFeats->filter(fn($f) => $f->level > 3)->map(fn($f) => "Lvl {$f->level}: {$f->feat_name}")->join(', ');

            $rows[] = [
                'id' => $p->id,
                'rank' => $p->rank ?? 'Üye',
                'character_name' => $p->character_name,
                'player_name' => $p->player_name ?? '-',
                'race' => $p->race,
                'class' => $p->class_name . ($p->archetype ? ' (' . $p->archetype . ')' : ''),
                'level' => $p->level,
                'lvl_1_standard' => $lvl1Standard ?: '-',
                'lvl_1_class_bonus' => $lvl1ClassBonus ?: '-',
                'lvl_1_racial_bonus' => $lvl1RacialBonus ?: '-',
                'lvl_2_class_bonus' => $lvl2ClassBonus ?: '-',
                'lvl_3_standard' => $lvl3Standard ?: '-',
                'higher_feats' => $higherFeats ?: '-',
                'total_feats' => $charFeats->count(),
            ];
        }

        return $rows;
    }

    /**
     * Calculate BAB and Saves from Pathfinder 1E tables.
     */
    public function calculateProgressionStats(string $className, int $level, array $abilities = []): array
    {
        $classSlug = strtolower(explode(' ', trim(explode('/', $className)[0]))[0]);

        // Load enums
        $loadEnum = function ($file) {
            $p = config_path('enums/' . $file);
            return file_exists($p) ? require $p : [];
        };

        $babTables = $loadEnum('bab.php');
        $classesInfo = $loadEnum('pathfinder_classes.php');

        $classInfo = $classesInfo[$classSlug] ?? null;
        $babKey = $classInfo['bab_table'] ?? 'bab_section_1';
        $hitDie = $classInfo['hit_die'] ?? 'd10';

        // BAB
        $babVal = $babTables[$babKey][$level] ?? '+1';

        // Base Saves: Good save vs Poor save in PF1E
        // Good save = +2 + level/2; Poor save = level/3
        $goodSave = 2 + (int) floor($level / 2);
        $poorSave = (int) floor($level / 3);

        $fort = $poorSave;
        $ref = $poorSave;
        $will = $poorSave;

        switch ($classSlug) {
            case 'fighter':
            case 'barbarian':
            case 'paladin':
            case 'bloodrager':
                $fort = $goodSave;
                break;
            case 'rogue':
            case 'bard':
                $ref = $goodSave;
                break;
            case 'cleric':
            case 'druid':
            case 'wizard':
            case 'sorcerer':
            case 'witch':
            case 'oracle':
                $will = $goodSave;
                break;
            case 'ranger':
            case 'slayer':
                $fort = $goodSave;
                $ref = $goodSave;
                break;
            case 'monk':
                $fort = $goodSave;
                $ref = $goodSave;
                $will = $goodSave;
                break;
            case 'magus':
            case 'alchemist':
            case 'inquisitor':
            case 'warpriest':
            case 'investigator':
                $fort = $goodSave;
                $will = $goodSave;
                break;
            default:
                $fort = $goodSave;
                break;
        }

        // Add ability modifiers
        $conMod = isset($abilities['con']) ? (int) floor(($abilities['con'] - 10) / 2) : 0;
        $dexMod = isset($abilities['dex']) ? (int) floor(($abilities['dex'] - 10) / 2) : 0;
        $wisMod = isset($abilities['wis']) ? (int) floor(($abilities['wis'] - 10) / 2) : 0;

        $totalFort = $fort + $conMod;
        $totalRef = $ref + $dexMod;
        $totalWill = $will + $wisMod;

        return [
            'bab' => $babVal,
            'fort' => ($totalFort >= 0 ? '+' : '') . $totalFort,
            'ref' => ($totalRef >= 0 ? '+' : '') . $totalRef,
            'will' => ($totalWill >= 0 ? '+' : '') . $totalWill,
            'hit_die' => $hitDie,
        ];
    }

    /**
     * Synchronize character feats to character profile sheet_data (both root and sheetData).
     */
    public function syncProfileFeatsSheetData(string $characterName): void
    {
        $profile = CharacterProfile::where('character_name', $characterName)->first();
        if (!$profile) {
            return;
        }

        $feats = CharacterFeat::where('character_name', $characterName)
            ->orderBy('level')
            ->orderBy('sort_order')
            ->get();

        $featList = [];
        foreach ($feats as $f) {
            $featList[] = [
                'title'          => $f->feat_name,
                'desc'           => $f->notes ?: '',
                'category'       => $f->category,
                'level'          => $f->level,
                'slot_type'      => $f->slot_type,
                'is_class_feat'  => (bool) $f->is_class_feat,
            ];
        }

        $sheet = is_array($profile->sheet_data) ? $profile->sheet_data : (json_decode($profile->sheet_data, true) ?: []);
        $sheet['feats'] = $featList;
        if (isset($sheet['sheetData']) && is_array($sheet['sheetData'])) {
            $sheet['sheetData']['feats'] = $featList;
        }
        $profile->sheet_data = $sheet;
        $profile->save();
    }

    /**
     * Add a feat to a character.
     */
    public function storeFeat(array $data): CharacterFeat
    {
        $feat = CharacterFeat::create([
            'character_name' => $data['character_name'],
            'level'          => $data['level'] ?? 1,
            'slot_type'      => $data['slot_type'] ?? CharacterFeat::SLOT_STANDARD,
            'feat_name'      => $data['feat_name'],
            'class_feat_name'=> $data['class_feat_name'] ?? null,
            'category'       => $data['category'] ?? 'General',
            'prerequisites'  => $data['prerequisites'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'is_class_feat'  => !empty($data['is_class_feat']) || in_array($data['slot_type'] ?? '', ['class_bonus', 'class_talent']),
            'sort_order'     => $data['sort_order'] ?? 0,
        ]);

        $this->syncProfileFeatsSheetData($data['character_name']);

        return $feat;
    }

    /**
     * Update an existing feat.
     */
    public function updateFeat(int $id, array $data): CharacterFeat
    {
        $feat = CharacterFeat::findOrFail($id);
        $feat->update([
            'level'          => $data['level'] ?? $feat->level,
            'slot_type'      => $data['slot_type'] ?? $feat->slot_type,
            'feat_name'      => $data['feat_name'] ?? $feat->feat_name,
            'class_feat_name'=> $data['class_feat_name'] ?? $feat->class_feat_name,
            'category'       => $data['category'] ?? $feat->category,
            'prerequisites'  => $data['prerequisites'] ?? $feat->prerequisites,
            'notes'          => $data['notes'] ?? $feat->notes,
            'is_class_feat'  => isset($data['is_class_feat']) ? (bool) $data['is_class_feat'] : $feat->is_class_feat,
        ]);

        $this->syncProfileFeatsSheetData($feat->character_name);

        return $feat;
    }

    /**
     * Delete a feat.
     */
    public function deleteFeat(int $id): bool
    {
        $feat = CharacterFeat::findOrFail($id);
        $charName = $feat->character_name;
        $deleted = (bool) $feat->delete();
        if ($deleted) {
            $this->syncProfileFeatsSheetData($charName);
        }
        return $deleted;
    }

    /**
     * Update character profile stats.
     */
    public function updateProfileStats(string $characterName, array $data): CharacterProfile
    {
        $profile = CharacterProfile::where('character_name', $characterName)->firstOrFail();
        $profile->update([
            'race'       => $data['race'] ?? $profile->race,
            'class_name' => $data['class_name'] ?? $profile->class_name,
            'archetype'  => $data['archetype'] ?? $profile->archetype,
            'level'      => $data['level'] ?? $profile->level,
            'rank'       => $data['rank'] ?? $profile->rank,
            'str'        => $data['str'] ?? $profile->str,
            'dex'        => $data['dex'] ?? $profile->dex,
            'con'        => $data['con'] ?? $profile->con,
            'int'        => $data['int'] ?? $profile->int,
            'wis'        => $data['wis'] ?? $profile->wis,
            'cha'        => $data['cha'] ?? $profile->cha,
            'hp'         => $data['hp'] ?? $profile->hp,
            'hit_die'    => $data['hit_die'] ?? $profile->hit_die,
            'bab'        => $data['bab'] ?? $profile->bab,
            'fort_save'  => $data['fort_save'] ?? $profile->fort_save,
            'ref_save'   => $data['ref_save'] ?? $profile->ref_save,
            'will_save'  => $data['will_save'] ?? $profile->will_save,
            'notes'      => $data['notes'] ?? $profile->notes,
        ]);

        // Keep Character model current_level in sync if it exists
        if (isset($data['level'])) {
            \App\Models\Character::where('name', $characterName)->update(['current_level' => (int) $data['level']]);
            if ($profile->character_id) {
                \App\Models\Character::where('id', $profile->character_id)->update(['current_level' => (int) $data['level']]);
            }
        }

        // Also ensure sheet_data root and nested sheetData have all updated stats
        $sheet = is_array($profile->sheet_data) ? $profile->sheet_data : (json_decode($profile->sheet_data, true) ?: []);
        $sheet['level'] = (int) $profile->level;
        $sheet['class'] = $profile->class_name;
        $sheet['className'] = $profile->class_name;
        $sheet['classKey'] = strtolower($profile->class_name);
        $sheet['race'] = $profile->race;
        $sheet['notes'] = $profile->notes;
        $sheet['hp'] = (int) $profile->hp;
        $sheet['maxHp'] = (int) $profile->hp;
        $sheet['currentHp'] = (int) $profile->hp;

        if (isset($sheet['sheetData']) && is_array($sheet['sheetData'])) {
            $sheet['sheetData']['level'] = (int) $profile->level;
            $sheet['sheetData']['class'] = $profile->class_name;
            $sheet['sheetData']['className'] = $profile->class_name;
            $sheet['sheetData']['classKey'] = strtolower($profile->class_name);
            $sheet['sheetData']['race'] = $profile->race;
            $sheet['sheetData']['currentHp'] = (int) $profile->hp;
            $sheet['sheetData']['maxHp'] = (int) $profile->hp;
            $sheet['sheetData']['spellNotes'] = $profile->notes;
            if (!isset($sheet['sheetData']['abilityScores'])) {
                $sheet['sheetData']['abilityScores'] = [];
            }
            $sheet['sheetData']['abilityScores']['str'] = (int) $profile->str;
            $sheet['sheetData']['abilityScores']['dex'] = (int) $profile->dex;
            $sheet['sheetData']['abilityScores']['con'] = (int) $profile->con;
            $sheet['sheetData']['abilityScores']['int'] = (int) $profile->int;
            $sheet['sheetData']['abilityScores']['wis'] = (int) $profile->wis;
            $sheet['sheetData']['abilityScores']['cha'] = (int) $profile->cha;
        }

        $profile->sheet_data = $sheet;
        $profile->save();

        return $profile;
    }

    /**
     * Delete a character and all their associated feats.
     */
    public function deleteCharacter(string $characterName): bool
    {
        return DB::transaction(function () use ($characterName) {
            CharacterFeat::where('character_name', $characterName)->delete();
            return (bool) CharacterProfile::where('character_name', $characterName)->delete();
        });
    }

    /**
     * Seed all initial default data from Google Sheet Image 1 and "Magus Buckler" Image 2.
     */
    public function seedDefaults(): void
    {
        if (!app()->environment('testing')) {
            return;
        }

        DB::transaction(function () {
            CharacterFeat::query()->delete();
            CharacterProfile::query()->delete();

            // 1. Magus Buckler (Exact copy of the reference "Turuncu Sayfa" Image 2!)
            CharacterProfile::create([
                'character_name' => 'Magus Buckler',
                'player_name'    => 'Dungeon Master',
                'race'           => 'Human',
                'class_name'     => 'Magus',
                'archetype'      => 'Bladebound + Kensai',
                'level'          => 12,
                'rank'           => 'Uzman',
                'str'            => 10,
                'dex'            => 18,
                'con'            => 14,
                'int'            => 18,
                'wis'            => 12,
                'cha'            => 10,
                'hp'             => 88,
                'max_hp'         => 88,
                'hit_die'        => 'd8',
                'bab'            => '+9/+4',
                'fort_save'      => '+10',
                'ref_save'       => '+8',
                'will_save'      => '+9',
                'notes'          => 'Dex-based Magus with rapier focus, shocking grasp specialization, and intensive metamagic.',
            ]);

            // Magus Buckler Feats from Image 2
            $magusFeats = [
                // Level 1: Additional Traits & Weapon Focus/Finesse
                ['level' => 1, 'slot_type' => 'trait', 'feat_name' => 'Additional Traits (Focused Mind, Anatomist)', 'category' => 'Trait'],
                ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Additional Traits (Magical Knack, Magical Lineage)', 'category' => 'Trait'],
                ['level' => 1, 'slot_type' => 'class_bonus', 'feat_name' => 'Weapon Focus (Rapier)', 'category' => 'Combat', 'is_class_feat' => true],
                ['level' => 1, 'slot_type' => 'class_bonus', 'feat_name' => 'Weapon Finesse (Rapier)', 'category' => 'Combat', 'is_class_feat' => true],
                // Level 2: Improved Critical (Rapier)
                ['level' => 2, 'slot_type' => 'class_bonus', 'feat_name' => 'Improved Critical (Rapier)', 'category' => 'Combat', 'is_class_feat' => true],
                // Level 3: Eclectic, Magus Arcana (Concentrate)
                ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Eclectic', 'category' => 'General'],
                ['level' => 3, 'slot_type' => 'class_talent', 'feat_name' => 'Magus Arcana (Concentrate)', 'category' => 'Magus Arcana', 'is_class_feat' => true],
                // Level 4: Quick Draw
                ['level' => 4, 'slot_type' => 'class_bonus', 'feat_name' => 'Quick Draw', 'category' => 'Combat', 'is_class_feat' => true],
                // Level 5: Weapon Specialization (Rapier)
                ['level' => 5, 'slot_type' => 'standard', 'feat_name' => 'Weapon Specialization (Rapier)', 'category' => 'Combat', 'prerequisites' => 'Weapon Focus(Rapier), Fighter 4 or Magus 5'],
                // Level 6: Magus Arcana (Close Range)
                ['level' => 6, 'slot_type' => 'class_talent', 'feat_name' => 'Magus Arcana (Close Range)', 'category' => 'Magus Arcana', 'is_class_feat' => true],
                // Level 7: Preq(Spell Focus(evocation),-)
                ['level' => 7, 'slot_type' => 'standard', 'feat_name' => 'Spell Focus (Evocation)', 'category' => 'Magic', 'prerequisites' => 'Preq(Spell Focus(evocation),-)'],
                // Level 8: Advanced Weapon Training (Warrior Spirit)
                ['level' => 8, 'slot_type' => 'class_talent', 'feat_name' => 'Advanced Weapon Training (Warrior Spirit)', 'category' => 'Combat', 'is_class_feat' => true],
                // Level 9: Spell Specialization (Shocking Grasp)
                ['level' => 9, 'slot_type' => 'standard', 'feat_name' => 'Spell Specialization (Shocking Grasp)', 'category' => 'Magic', 'prerequisites' => 'Spell Focus (Evocation)'],
                // Level 11: Intensified Spell
                ['level' => 11, 'slot_type' => 'standard', 'feat_name' => 'Intensified Spell', 'category' => 'Metamagic'],
            ];

            foreach ($magusFeats as $mf) {
                CharacterFeat::create(array_merge($mf, ['character_name' => 'Magus Buckler']));
            }

            // 2. All Characters from Master Sheet (Image 1)
            $sheetCharacters = [
                [
                    'character_name' => 'Arelion Vane',
                    'player_name'    => 'İmge Dağlar',
                    'rank'           => 'Uzman',
                    'race'           => 'Human',
                    'class_name'     => 'Inquisitor / Rogue',
                    'level'          => 11,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Weapon Focus (Dagger)', 'category' => 'Combat'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Weapon Finesse (Dagger)', 'category' => 'Combat'],
                        ['level' => 2, 'slot_type' => 'class_talent', 'feat_name' => 'Rogue Talent (Bleeding Attack)', 'category' => 'Rogue Talent', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Piranha Strike', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'class_bonus', 'feat_name' => 'Teamwork: Precise Strike', 'category' => 'Teamwork', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Argon Ranfer',
                    'player_name'    => 'Onur Kubilay',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Human',
                    'class_name'     => 'Ranger',
                    'archetype'      => 'Demon Slayer',
                    'level'          => 7,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Exotic Weapon Proficiency (Butchering Axe)', 'category' => 'Combat'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Heavy Armor Proficiency', 'category' => 'Combat'],
                        ['level' => 2, 'slot_type' => 'class_bonus', 'feat_name' => 'Ranger Combat Styles (Mighty Strike - Power Attack)', 'category' => 'Combat', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Furious Focus', 'category' => 'Combat'],
                        ['level' => 5, 'slot_type' => 'standard', 'feat_name' => 'Cleave', 'category' => 'Combat'],
                        ['level' => 6, 'slot_type' => 'class_bonus', 'feat_name' => 'Ranger Combat Styles (Great Cleave)', 'category' => 'Combat', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Arin',
                    'player_name'    => 'Barış Bingöl',
                    'rank'           => 'Üye',
                    'race'           => 'Human',
                    'class_name'     => 'Bard',
                    'level'          => 2,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Skill Focus (Diplomacy)', 'category' => 'General'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Extra Performance', 'category' => 'General'],
                    ],
                ],
                [
                    'character_name' => 'Azariah',
                    'player_name'    => 'Hür Ulay',
                    'rank'           => 'Uzman',
                    'race'           => 'Half-Elf',
                    'class_name'     => 'Oracle',
                    'archetype'      => 'Divine Scion',
                    'level'          => 12,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Weapon Focus (Longsword)', 'category' => 'Combat', 'prerequisites' => 'Preq(Iron Will/Weapon Focus(Longsword))'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Iron Will', 'category' => 'General'],
                        ['level' => 5, 'slot_type' => 'standard', 'feat_name' => 'Extra Revelation', 'category' => 'General'],
                    ],
                ],
                [
                    'character_name' => 'Balmond',
                    'player_name'    => 'Şevket Erdoğan',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Half-Orc',
                    'class_name'     => 'Magus',
                    'archetype'      => 'Eldritch Archer',
                    'level'          => 8,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Point-Blank Shot', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Precise Shot', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'class_talent', 'feat_name' => 'Magus Arcana (Arcane Accuracy)', 'category' => 'Magus Arcana', 'is_class_feat' => true],
                        ['level' => 5, 'slot_type' => 'standard', 'feat_name' => 'Rapid Shot', 'category' => 'Combat'],
                        ['level' => 5, 'slot_type' => 'class_bonus', 'feat_name' => 'Weapon Focus (Longbow)', 'category' => 'Combat', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Bart',
                    'player_name'    => 'Yakup Çakmak',
                    'rank'           => 'Uzman',
                    'race'           => 'Human',
                    'class_name'     => 'Cavalier',
                    'archetype'      => 'Sorcerer / Bard',
                    'level'          => 14,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Mounted Combat', 'category' => 'Combat'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Ride-By Attack', 'category' => 'Combat'],
                        ['level' => 1, 'slot_type' => 'class_bonus', 'feat_name' => 'Tactician: Coordinated Charge', 'category' => 'Teamwork', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Spirited Charge', 'category' => 'Combat'],
                    ],
                ],
                [
                    'character_name' => 'Baulbazahr',
                    'player_name'    => 'Volkan Öztaşçı',
                    'rank'           => 'Uzman',
                    'race'           => 'Grippli',
                    'class_name'     => 'Inquisitor',
                    'level'          => 12,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Improved Initiative', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Weapon Focus (Net)', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'class_bonus', 'feat_name' => 'Teamwork: Lookout', 'category' => 'Teamwork', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Belle',
                    'player_name'    => 'Nazlıcan Kat',
                    'rank'           => 'Uzman',
                    'race'           => 'Aasimar',
                    'class_name'     => 'Wizard',
                    'archetype'      => 'Evoker',
                    'level'          => 10,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Spell Focus (Evocation)', 'category' => 'Magic'],
                        ['level' => 1, 'slot_type' => 'class_bonus', 'feat_name' => 'Scribe Scroll', 'category' => 'Item Creation', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Greater Spell Focus (Evocation)', 'category' => 'Magic'],
                        ['level' => 5, 'slot_type' => 'standard', 'feat_name' => 'Empower Spell', 'category' => 'Metamagic'],
                        ['level' => 5, 'slot_type' => 'class_bonus', 'feat_name' => 'Craft Wondrous Item', 'category' => 'Item Creation', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Bisco',
                    'player_name'    => 'Ege Çelik',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Half-Orc',
                    'class_name'     => 'Bloodrager',
                    'archetype'      => 'Dragon Disciple',
                    'level'          => 8,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Ferocious Resolve', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Power Attack', 'category' => 'Combat'],
                        ['level' => 5, 'slot_type' => 'standard', 'feat_name' => 'Raging Vitality', 'category' => 'Combat'],
                        ['level' => 6, 'slot_type' => 'class_bonus', 'feat_name' => 'Toughness (Bloodline)', 'category' => 'General', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Cey-Cey',
                    'player_name'    => 'Batu Bilekli',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Human',
                    'class_name'     => 'Mesmerist',
                    'level'          => 7,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Extended Stare', 'category' => 'General'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Spell Focus (Enchantment)', 'category' => 'Magic'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Intimidating Glance', 'category' => 'General'],
                    ],
                ],
                [
                    'character_name' => "De-ak T'aur",
                    'player_name'    => 'Mehmet Helvacı',
                    'rank'           => 'Uzman',
                    'race'           => 'Half-Orc',
                    'class_name'     => 'Warpriest',
                    'archetype'      => 'ShieldBearer',
                    'level'          => 11,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Two-Weapon Fighting', 'category' => 'Combat', 'prerequisites' => 'Preq(Two-Weapon Fighting, Shield Slam)'],
                        ['level' => 1, 'slot_type' => 'class_bonus', 'feat_name' => 'Weapon Focus (Shield)', 'category' => 'Combat', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Shield Slam', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'class_bonus', 'feat_name' => 'Improved Shield Bash', 'category' => 'Combat', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Desen',
                    'player_name'    => 'Furkan Balık',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Human',
                    'class_name'     => 'Antipaladin',
                    'archetype'      => 'Insinuator',
                    'level'          => 9,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Power Attack', 'category' => 'Combat', 'prerequisites' => 'Preq(Persuasive/-)'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Sense Loyalties', 'category' => 'General'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Weapon Focus (Greatsword)', 'category' => 'Combat'],
                    ],
                ],
                [
                    'character_name' => 'Doran',
                    'player_name'    => 'Baran Uzun',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Human',
                    'class_name'     => 'Hunter',
                    'level'          => 6,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Point Blank Shot', 'category' => 'Combat'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Precise Shot', 'category' => 'Combat'],
                        ['level' => 2, 'slot_type' => 'class_bonus', 'feat_name' => 'Outflank (Teamwork)', 'category' => 'Teamwork', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Rapid Shot', 'category' => 'Combat'],
                    ],
                ],
                [
                    'character_name' => 'Duke',
                    'player_name'    => 'Mert Doyurgan',
                    'rank'           => 'Uzman',
                    'race'           => 'Human',
                    'class_name'     => 'Paladin',
                    'archetype'      => 'Iomedaean Champion',
                    'level'          => 12,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => "Heaven's Light", 'category' => 'General'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Extra Lay On Hands', 'category' => 'General'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Power Attack', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'class_talent', 'feat_name' => 'Mercy (Fatigued)', 'category' => 'Paladin Mercy', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Ember Maiden',
                    'player_name'    => 'Mert Şanal',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Ifrit',
                    'class_name'     => 'Fighter',
                    'level'          => 9,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Power Attack', 'category' => 'Combat'],
                        ['level' => 1, 'slot_type' => 'class_bonus', 'feat_name' => 'Furious Focus', 'category' => 'Combat', 'is_class_feat' => true],
                        ['level' => 2, 'slot_type' => 'class_bonus', 'feat_name' => 'Weapon Focus (Earthbreaker)', 'category' => 'Combat', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Weapon Specialization (Earthbreaker)', 'category' => 'Combat'],
                        ['level' => 4, 'slot_type' => 'class_bonus', 'feat_name' => 'Vital Strike', 'category' => 'Combat', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Ezekiel',
                    'player_name'    => 'Ozan Güzel',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Human',
                    'class_name'     => 'Paladin',
                    'archetype'      => 'Warrior of the Holy Light',
                    'level'          => 11,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Extra Lay On Hands', 'category' => 'General'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Extra Mercy', 'category' => 'General'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Power Attack', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'class_talent', 'feat_name' => 'Mercy (Sickened)', 'category' => 'Paladin Mercy', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Fizzymir Flatfoot',
                    'player_name'    => 'Bengi Çoban Kılıç',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Human',
                    'class_name'     => 'Alchemist',
                    'level'          => 6,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Point-Blank Shot', 'category' => 'Combat'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Precise Shot', 'category' => 'Combat'],
                        ['level' => 2, 'slot_type' => 'class_talent', 'feat_name' => 'Discovery (Precise Bomb)', 'category' => 'Discovery', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Extra Discovery (Infusion)', 'category' => 'General'],
                        ['level' => 4, 'slot_type' => 'class_talent', 'feat_name' => 'Discovery (Smoke Bomb)', 'category' => 'Discovery', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Fırıldak',
                    'player_name'    => 'Deniz Hepsakaryalı',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Halfling',
                    'class_name'     => 'Ranger',
                    'level'          => 9,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Point Blank Shot', 'category' => 'Combat'],
                        ['level' => 2, 'slot_type' => 'class_bonus', 'feat_name' => 'Ranger Combat Styles (Archery: Rapid Shot)', 'category' => 'Combat', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Precise Shot', 'category' => 'Combat'],
                    ],
                ],
                [
                    'character_name' => 'İda',
                    'player_name'    => 'Öykü Demir',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Human',
                    'class_name'     => 'Druid',
                    'level'          => 7,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Point-Blank Shot', 'category' => 'Combat'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Precise Shot', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Natural Spell', 'category' => 'Magic'],
                    ],
                ],
                [
                    'character_name' => 'Ignis Nyx',
                    'player_name'    => 'Mehmet Ali Bahit',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Human',
                    'class_name'     => 'Witch',
                    'level'          => 7,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Elemental Focus (Fire)', 'category' => 'Magic'],
                        ['level' => 1, 'slot_type' => 'class_talent', 'feat_name' => 'Hex (Misfortune)', 'category' => 'Witch Hex', 'is_class_feat' => true],
                        ['level' => 2, 'slot_type' => 'class_talent', 'feat_name' => 'Hex (Evil Eye)', 'category' => 'Witch Hex', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Accursed Hex', 'category' => 'Magic'],
                    ],
                ],
                [
                    'character_name' => 'Ivaria',
                    'player_name'    => 'Baran Sözmen',
                    'rank'           => 'Uzman',
                    'race'           => 'Drow',
                    'class_name'     => 'Slayer',
                    'archetype'      => 'Sniper / Stygian Slayer',
                    'level'          => 11,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Drow Nobility', 'category' => 'General'],
                        ['level' => 2, 'slot_type' => 'class_talent', 'feat_name' => 'Slayer Talents (Ranger Combat Styles: Rapid Shot)', 'category' => 'Slayer Talent', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Improved Drow Nobility', 'category' => 'General'],
                    ],
                ],
                [
                    'character_name' => 'Kerrik',
                    'player_name'    => 'Alican Öztürk',
                    'rank'           => 'Uzman',
                    'race'           => 'Gnome',
                    'class_name'     => 'Summoner',
                    'level'          => 11,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Extra Evolution', 'category' => 'General'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Augment Summoning', 'category' => 'Magic'],
                        ['level' => 5, 'slot_type' => 'standard', 'feat_name' => 'Extra Evolution', 'category' => 'General'],
                    ],
                ],
                [
                    'character_name' => 'Mert',
                    'player_name'    => 'Mert Burgu',
                    'rank'           => 'Uzman',
                    'race'           => 'Dwarf',
                    'class_name'     => 'Warpriest',
                    'archetype'      => 'Forgepriest',
                    'level'          => 10,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Steel Soul', 'category' => 'General'],
                        ['level' => 1, 'slot_type' => 'class_bonus', 'feat_name' => 'Weapon Focus (Dwarven Maulaxe)', 'category' => 'Combat', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Power Attack', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'class_bonus', 'feat_name' => 'Craft Magic Arms and Armor', 'category' => 'Item Creation', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Mirang',
                    'player_name'    => 'Ozan Akça',
                    'rank'           => 'Uzman',
                    'race'           => 'Gnome',
                    'class_name'     => 'Arcanist',
                    'level'          => 11,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Spell Focus (Illusion)', 'category' => 'Magic'],
                        ['level' => 1, 'slot_type' => 'class_talent', 'feat_name' => 'Arcanist Exploit (Bloodline: Ectoplasm Bloodline)', 'category' => 'Arcanist Exploit', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Greater Spell Focus (Illusion)', 'category' => 'Magic'],
                    ],
                ],
                [
                    'character_name' => 'Mordo Medo',
                    'player_name'    => 'Mert Gürgen',
                    'rank'           => 'Uzman',
                    'race'           => 'Human',
                    'class_name'     => 'Monk',
                    'archetype'      => 'Master of Many Styles',
                    'level'          => 11,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Snapping Turtle Style', 'category' => 'Style'],
                        ['level' => 1, 'slot_type' => 'class_bonus', 'feat_name' => 'Improved Grapple', 'category' => 'Combat', 'is_class_feat' => true],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Crane Style', 'category' => 'Style'],
                        ['level' => 2, 'slot_type' => 'class_bonus', 'feat_name' => 'Dragon Style', 'category' => 'Style', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Snapping Turtle Clutch', 'category' => 'Style'],
                    ],
                ],
                [
                    'character_name' => 'Noodle',
                    'player_name'    => 'Yaren Kalaylıoğlu',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Human',
                    'class_name'     => 'Wizard',
                    'level'          => 10,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Extra Cantrips', 'category' => 'Magic'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Burning Amplification', 'category' => 'Magic'],
                        ['level' => 1, 'slot_type' => 'class_bonus', 'feat_name' => 'Scribe Scroll', 'category' => 'Item Creation', 'is_class_feat' => true],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Spell Focus (Evocation)', 'category' => 'Magic'],
                    ],
                ],
                [
                    'character_name' => 'Raphael',
                    'player_name'    => 'Baver Erdem',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Aasimar',
                    'class_name'     => 'Paladin',
                    'level'          => 9,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Extra Lay on Hands', 'category' => 'General'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Power Attack', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'class_talent', 'feat_name' => 'Mercy (Diseased)', 'category' => 'Paladin Mercy', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Raska',
                    'player_name'    => 'Beri Genç',
                    'rank'           => 'Uzman',
                    'race'           => 'Elf',
                    'class_name'     => 'Shifter',
                    'level'          => 11,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Weapon Focus (Claw)', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Feral Combat Training', 'category' => 'Combat'],
                        ['level' => 5, 'slot_type' => 'standard', 'feat_name' => 'Multiattack', 'category' => 'Combat'],
                    ],
                ],
                [
                    'character_name' => 'Sango',
                    'player_name'    => 'Göker Erol',
                    'rank'           => 'Uzman',
                    'race'           => 'Human',
                    'class_name'     => 'Investigator',
                    'archetype'      => 'Empiricist / Hunter',
                    'level'          => 12,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Deft Hands', 'category' => 'General'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Persuasive', 'category' => 'General'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Weapon Finesse', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'class_talent', 'feat_name' => 'Investigator Talent (Expanded Inspiration)', 'category' => 'Investigator Talent', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Sangruiel Dawnbreaker',
                    'player_name'    => 'Ali Karaoğlan',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Aasimar',
                    'class_name'     => 'Inquisitor',
                    'level'          => 10,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Exotic Weapon Proficiency (Fauchard)', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Power Attack', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'class_bonus', 'feat_name' => 'Teamwork: Outflank', 'category' => 'Teamwork', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Taro',
                    'player_name'    => 'Temmuz Karakum',
                    'rank'           => 'Uzman',
                    'race'           => 'Human',
                    'class_name'     => 'Magus',
                    'archetype'      => 'Bladebound + Hexcrafter',
                    'level'          => 12,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Combat Reflexes', 'category' => 'Combat'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => 'Weapon Focus (Rapier)', 'category' => 'Combat'],
                        ['level' => 3, 'slot_type' => 'class_talent', 'feat_name' => 'Hex (Slumber)', 'category' => 'Witch Hex', 'is_class_feat' => true],
                        ['level' => 5, 'slot_type' => 'standard', 'feat_name' => 'Dervish Dance', 'category' => 'Combat'],
                        ['level' => 5, 'slot_type' => 'class_bonus', 'feat_name' => 'Accursed Hex', 'category' => 'Magic', 'is_class_feat' => true],
                    ],
                ],
                [
                    'character_name' => 'Temran',
                    'player_name'    => 'Veysel Anıl Erdoğan',
                    'rank'           => 'Kıdemli',
                    'race'           => 'Human',
                    'class_name'     => 'Cleric',
                    'level'          => 9,
                    'feats'          => [
                        ['level' => 1, 'slot_type' => 'standard', 'feat_name' => 'Warrior Priest', 'category' => 'Combat'],
                        ['level' => 1, 'slot_type' => 'racial_bonus', 'feat_name' => "Healer's Touch", 'category' => 'General'],
                        ['level' => 3, 'slot_type' => 'standard', 'feat_name' => 'Selective Channeling', 'category' => 'General'],
                        ['level' => 5, 'slot_type' => 'standard', 'feat_name' => 'Extra Channel', 'category' => 'General'],
                    ],
                ],
            ];

            foreach ($sheetCharacters as $sc) {
                $feats = $sc['feats'] ?? [];
                unset($sc['feats']);

                $profile = CharacterProfile::create(array_merge([
                    'str' => 12, 'dex' => 14, 'con' => 14, 'int' => 12, 'wis' => 12, 'cha' => 10,
                    'hp' => 60, 'max_hp' => 60,
                ], $sc));

                foreach ($feats as $f) {
                    CharacterFeat::create(array_merge($f, ['character_name' => $profile->character_name]));
                }
            }
        });
    }

    /**
     * Feat Autocomplete / Library suggestions for UI modals.
     */
    public function getCommonFeatsLibrary(): array
    {
        return [
            // Combat Feats
            ['name' => 'Power Attack', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Str 13, BAB +1'],
            ['name' => 'Weapon Focus', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Proficiency, BAB +1'],
            ['name' => 'Weapon Finesse', 'type' => 'Combat', 'category' => 'Combat', 'preq' => '—'],
            ['name' => 'Point-Blank Shot', 'type' => 'Combat', 'category' => 'Combat', 'preq' => '—'],
            ['name' => 'Precise Shot', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Point-Blank Shot'],
            ['name' => 'Rapid Shot', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Dex 13, Point-Blank Shot'],
            ['name' => 'Improved Initiative', 'type' => 'Combat', 'category' => 'Combat', 'preq' => '—'],
            ['name' => 'Combat Reflexes', 'type' => 'Combat', 'category' => 'Combat', 'preq' => '—'],
            ['name' => 'Furious Focus', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Str 13, Power Attack, BAB +1'],
            ['name' => 'Vital Strike', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'BAB +6'],
            ['name' => 'Weapon Specialization', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Weapon Focus, Fighter 4 or Magus 5'],
            ['name' => 'Greater Weapon Focus', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Weapon Focus, Fighter 8'],
            ['name' => 'Improved Critical', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Proficiency, BAB +8'],
            ['name' => 'Two-Weapon Fighting', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Dex 15'],
            ['name' => 'Shield Slam', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Improved Shield Bash, Two-Weapon Fighting, BAB +6'],
            ['name' => 'Cleave', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Str 13, Power Attack, BAB +1'],
            ['name' => 'Great Cleave', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Cleave, BAB +4'],
            ['name' => 'Quick Draw', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'BAB +1'],
            ['name' => 'Dodge', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Dex 13'],
            ['name' => 'Mobility', 'type' => 'Combat', 'category' => 'Combat', 'preq' => 'Dodge'],

            // Metamagic & Spells
            ['name' => 'Intensified Spell', 'type' => 'Metamagic', 'category' => 'Metamagic', 'preq' => '—'],
            ['name' => 'Empower Spell', 'type' => 'Metamagic', 'category' => 'Metamagic', 'preq' => '—'],
            ['name' => 'Maximize Spell', 'type' => 'Metamagic', 'category' => 'Metamagic', 'preq' => '—'],
            ['name' => 'Quicken Spell', 'type' => 'Metamagic', 'category' => 'Metamagic', 'preq' => '—'],
            ['name' => 'Spell Focus', 'type' => 'Magic', 'category' => 'Magic', 'preq' => '—'],
            ['name' => 'Greater Spell Focus', 'type' => 'Magic', 'category' => 'Magic', 'preq' => 'Spell Focus'],
            ['name' => 'Spell Specialization', 'type' => 'Magic', 'category' => 'Magic', 'preq' => 'Spell Focus'],
            ['name' => 'Elemental Focus', 'type' => 'Magic', 'category' => 'Magic', 'preq' => '—'],

            // Rogue Talents
            ['name' => 'Rogue Talent (Bleeding Attack)', 'type' => 'Class Talent', 'category' => 'Rogue Talent', 'preq' => 'Sneak attack'],
            ['name' => 'Rogue Talent (Combat Trick)', 'type' => 'Class Talent', 'category' => 'Rogue Talent', 'preq' => '—'],
            ['name' => 'Rogue Talent (Fast Stealth)', 'type' => 'Class Talent', 'category' => 'Rogue Talent', 'preq' => '—'],
            ['name' => 'Rogue Talent (Weapon Training)', 'type' => 'Class Talent', 'category' => 'Rogue Talent', 'preq' => '—'],
            ['name' => 'Rogue Talent (Finesse Rogue)', 'type' => 'Class Talent', 'category' => 'Rogue Talent', 'preq' => '—'],

            // Magus Arcana
            ['name' => 'Magus Arcana (Concentrate)', 'type' => 'Class Talent', 'category' => 'Magus Arcana', 'preq' => 'Magus 3'],
            ['name' => 'Magus Arcana (Close Range)', 'type' => 'Class Talent', 'category' => 'Magus Arcana', 'preq' => 'Magus 3'],
            ['name' => 'Magus Arcana (Arcane Accuracy)', 'type' => 'Class Talent', 'category' => 'Magus Arcana', 'preq' => 'Magus 3'],
            ['name' => 'Magus Arcana (Empowered Magic)', 'type' => 'Class Talent', 'category' => 'Magus Arcana', 'preq' => 'Magus 6'],

            // Discoveries & Hexes
            ['name' => 'Discovery (Precise Bomb)', 'type' => 'Class Talent', 'category' => 'Discovery', 'preq' => 'Alchemist 2'],
            ['name' => 'Discovery (Infusion)', 'type' => 'Class Talent', 'category' => 'Discovery', 'preq' => 'Alchemist 2'],
            ['name' => 'Discovery (Smoke Bomb)', 'type' => 'Class Talent', 'category' => 'Discovery', 'preq' => 'Alchemist 2'],
            ['name' => 'Hex (Evil Eye)', 'type' => 'Class Talent', 'category' => 'Witch Hex', 'preq' => 'Witch 1'],
            ['name' => 'Hex (Misfortune)', 'type' => 'Class Talent', 'category' => 'Witch Hex', 'preq' => 'Witch 1'],
            ['name' => 'Hex (Slumber)', 'type' => 'Class Talent', 'category' => 'Witch Hex', 'preq' => 'Witch 1'],

            // General & Traits
            ['name' => 'Additional Traits', 'type' => 'Trait', 'category' => 'Trait', 'preq' => '—'],
            ['name' => 'Toughness', 'type' => 'General', 'category' => 'General', 'preq' => '—'],
            ['name' => 'Iron Will', 'type' => 'General', 'category' => 'General', 'preq' => '—'],
            ['name' => 'Steel Soul', 'type' => 'General', 'category' => 'General', 'preq' => 'Dwarf'],
        ];
    }
}
