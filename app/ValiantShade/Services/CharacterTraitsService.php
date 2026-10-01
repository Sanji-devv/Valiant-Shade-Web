<?php

namespace App\ValiantShade\Services;

use App\Models\Character;
use App\Models\CharacterProfile;
use App\Models\CharacterTrait;
use App\Models\Rank;
use Illuminate\Support\Str;

class CharacterTraitsService
{
    protected const RANK_HIERARCHY = [
        'aday'         => 1,
        'çırak'        => 2,
        'cirak'        => 2,
        'clerk'        => 2,
        'üye'          => 3,
        'uye'          => 3,
        'kıdemli'      => 4,
        'kidemli'      => 4,
        'uzman'        => 5,
        'usta'         => 6,
        'margrave'     => 6,
        'margrave (u)' => 7,
        'büyük usta'   => 7,
        'buyuk usta'   => 7,
        'temsilci'     => 8,
        'lonca lideri' => 8,
    ];

    /**
     * Get hierarchy level for a rank string or Rank model.
     */
    public function getRankHierarchy($rank): int
    {
        if ($rank instanceof Rank) {
            return (int) ($rank->hierarchy_order ?? $rank->hierarchy_level ?? self::RANK_HIERARCHY[mb_strtolower($rank->name)] ?? 0);
        }

        $rName = mb_strtolower(trim((string)$rank));
        if (isset(self::RANK_HIERARCHY[$rName])) {
            return self::RANK_HIERARCHY[$rName];
        }

        // Try lookup in DB
        $dbRank = Rank::whereRaw('LOWER(name) = ?', [$rName])->first();
        if ($dbRank && ($dbRank->hierarchy_order || $dbRank->hierarchy_level)) {
            return (int) ($dbRank->hierarchy_order ?: $dbRank->hierarchy_level);
        }

        return 0;
    }

    /**
     * Resolve actual guild rank for character (checking Character model and CharacterProfile).
     */
    public function resolveCharacterRank(string $characterName, ?CharacterProfile $profile = null): string
    {
        $char = Character::where('name', $characterName)->with('rank')->first();
        if (!$char && $profile?->character_id) {
            $char = Character::where('id', $profile->character_id)->with('rank')->first();
        }

        $charRank = $char?->rank?->name;
        $charH = $charRank ? $this->getRankHierarchy($charRank) : 0;

        if (!$profile) {
            $profile = CharacterProfile::where('character_name', $characterName)->first();
        }

        $profRank = $profile?->rank;
        $profH = $profRank ? $this->getRankHierarchy($profRank) : 0;

        if ($charH >= $profH && $charH > 0) {
            if ($profile && $profile->rank !== $charRank && !empty($charRank)) {
                $profile->rank = $charRank;
                $profile->save();
            }
            return $charRank;
        }

        if ($profH > 0) {
            return $profRank;
        }

        return $charRank ?: ($profRank ?: 'Üye');
    }

    /**
     * Get progression and unlock status for character traits (Üye, Kıdemli, Uzman).
     */
    public function getCharacterTraitsProgression(string $characterName, ?CharacterProfile $profile = null): array
    {
        if (!$profile) {
            $profile = CharacterProfile::where('character_name', $characterName)->first();
        }

        $rankName = $this->resolveCharacterRank($characterName, $profile);
        $hierarchy = $this->getRankHierarchy($rankName);
        if ($hierarchy <= 0) {
            $hierarchy = 3; // Fallback to Üye if still unrecognized
        }

        // Required hierarchies:
        // Üye (1. Slot): hierarchy >= 3
        // Kıdemli (2. Slot): hierarchy >= 4
        // Uzman (3. Slot): hierarchy >= 5
        $unlockedUye = $hierarchy >= 3;
        $unlockedKidemli = $hierarchy >= 4;
        $unlockedUzman = $hierarchy >= 5;

        $dbTraits = CharacterTrait::where('character_name', $characterName)->get()->keyBy('slot_key');
        $sheetTraits = is_array($profile?->sheet_data) ? ($profile->sheet_data['traits'] ?? []) : [];

        $slots = [
            'uye' => [
                'slot_key' => 'uye',
                'slot_name' => 'Üye',
                'slot_label' => 'Üye Traiti (1. Slot)',
                'required_rank' => 'Üye',
                'required_hierarchy' => 3,
                'is_unlocked' => $unlockedUye,
                'trait' => $dbTraits->get('uye') ?: (object)[
                    'trait_name' => $sheetTraits['uye']['name'] ?? null,
                    'category' => null,
                    'description' => $sheetTraits['uye']['desc'] ?? null,
                    'source_url' => null,
                ],
            ],
            'kidemli' => [
                'slot_key' => 'kidemli',
                'slot_name' => 'Kıdemli',
                'slot_label' => 'Kıdemli Traiti (2. Slot)',
                'required_rank' => 'Kıdemli',
                'required_hierarchy' => 4,
                'is_unlocked' => $unlockedKidemli,
                'trait' => $dbTraits->get('kidemli') ?: (object)[
                    'trait_name' => $sheetTraits['kidemli']['name'] ?? null,
                    'category' => null,
                    'description' => $sheetTraits['kidemli']['desc'] ?? null,
                    'source_url' => null,
                ],
            ],
            'uzman' => [
                'slot_key' => 'uzman',
                'slot_name' => 'Uzman',
                'slot_label' => 'Uzman Traiti (3. Slot)',
                'required_rank' => 'Uzman',
                'required_hierarchy' => 5,
                'is_unlocked' => $unlockedUzman,
                'trait' => $dbTraits->get('uzman') ?: (object)[
                    'trait_name' => $sheetTraits['uzman']['name'] ?? null,
                    'category' => null,
                    'description' => $sheetTraits['uzman']['desc'] ?? null,
                    'source_url' => null,
                ],
            ],
        ];

        return $slots;
    }

    /**
     * Save trait to database and character profile sheet data.
     */
    public function saveTrait(string $characterName, string $slotKey, array $data): array
    {
        $profile = CharacterProfile::where('character_name', $characterName)->first();
        $progression = $this->getCharacterTraitsProgression($characterName, $profile);

        if (!isset($progression[$slotKey])) {
            return [
                'success' => false,
                'status' => 422,
                'message' => __('Geçersiz trait slotu.'),
            ];
        }

        $slot = $progression[$slotKey];
        if (!$slot['is_unlocked']) {
            return [
                'success' => false,
                'status' => 422,
                'message' => __(':rank rütbesi henüz açılmadı. Bu trait slotu kilitlidir.', ['rank' => $slot['required_rank']]),
            ];
        }

        $char = Character::where('name', $characterName)->first();

        $sourceUrl = $data['source_url'] ?? null;
        if (empty($sourceUrl) && !empty($data['trait_name'])) {
            $matched = $this->searchCatalog($data['trait_name'], 1);
            if (!empty($matched) && mb_strtolower(trim($matched[0]['name'] ?? '')) === mb_strtolower(trim($data['trait_name']))) {
                $sourceUrl = $matched[0]['url'] ?? null;
            }
            if (empty($sourceUrl)) {
                $sourceUrl = 'https://www.d20pfsrd.com/?s=' . urlencode($data['trait_name']);
            }
        }

        $cTrait = CharacterTrait::updateOrCreate(
            [
                'character_name' => $characterName,
                'slot_key' => $slotKey,
            ],
            [
                'character_id' => $char?->id,
                'slot_name' => $slot['slot_name'],
                'required_rank' => $slot['required_rank'],
                'required_hierarchy' => $slot['required_hierarchy'],
                'trait_name' => $data['trait_name'] ?? null,
                'category' => $data['category'] ?? null,
                'description' => $data['description'] ?? null,
                'source_url' => $sourceUrl,
            ]
        );

        // Update profile sheet data
        if ($profile) {
            $sheetData = is_array($profile->sheet_data) ? $profile->sheet_data : (json_decode($profile->sheet_data, true) ?: []);
            if (!isset($sheetData['traits'])) {
                $sheetData['traits'] = [];
            }
            $traitEntry = [
                'name' => $cTrait->trait_name,
                'desc' => $cTrait->description,
                'category' => $cTrait->category,
                'source_url' => $cTrait->source_url,
            ];
            $sheetData['traits'][$slotKey] = $traitEntry;

            if (isset($sheetData['sheetData']) && is_array($sheetData['sheetData'])) {
                if (!isset($sheetData['sheetData']['traits'])) {
                    $sheetData['sheetData']['traits'] = [];
                }
                $sheetData['sheetData']['traits'][$slotKey] = $traitEntry;
            }

            $profile->sheet_data = $sheetData;
            $profile->save();
        }

        return [
            'success' => true,
            'status' => 200,
            'message' => __('Trait başarıyla kaydedildi.'),
            'trait' => $cTrait,
        ];
    }

    /**
     * Clear an assigned trait from database and character profile sheet data.
     */
    public function clearTrait(string $characterName, string $slotKey): array
    {
        $cTrait = CharacterTrait::where('character_name', $characterName)
            ->where('slot_key', $slotKey)
            ->first();

        if ($cTrait) {
            $cTrait->update([
                'trait_name' => null,
                'category' => null,
                'description' => null,
                'source_url' => null,
            ]);
        }

        $profile = CharacterProfile::where('character_name', $characterName)->first();
        if ($profile) {
            $sheetData = is_array($profile->sheet_data) ? $profile->sheet_data : (json_decode($profile->sheet_data, true) ?: []);
            $emptyTrait = [
                'name' => '',
                'desc' => '',
                'category' => null,
                'source_url' => null,
            ];
            if (!isset($sheetData['traits'])) {
                $sheetData['traits'] = [];
            }
            $sheetData['traits'][$slotKey] = $emptyTrait;

            if (isset($sheetData['sheetData']) && is_array($sheetData['sheetData'])) {
                if (!isset($sheetData['sheetData']['traits'])) {
                    $sheetData['sheetData']['traits'] = [];
                }
                $sheetData['sheetData']['traits'][$slotKey] = $emptyTrait;
            }

            $profile->sheet_data = $sheetData;
            $profile->save();
        }

        return [
            'success' => true,
            'status' => 200,
            'message' => __('Trait temizlendi.'),
        ];
    }

    /**
     * Search Pathfinder traits enum catalog.
     */
    public function searchCatalog(string $query, int $limit = 50): array
    {
        $traitsFile = file_exists(config_path('enums/traits.php'))
            ? config_path('enums/traits.php')
            : (file_exists(config_path('enums/pathfinder_traits.php')) ? config_path('enums/pathfinder_traits.php') : null);

        $allTraits = $traitsFile ? require $traitsFile : [];
        if (empty($query)) {
            return array_slice($allTraits, 0, $limit);
        }

        $cleanQ = mb_strtolower(trim($query));
        $matches = [];

        foreach ($allTraits as $t) {
            $name = mb_strtolower($t['name'] ?? '');
            $category = mb_strtolower($t['category'] ?? '');
            $desc = mb_strtolower($t['description'] ?? '');

            if (str_contains($name, $cleanQ) || str_contains($category, $cleanQ) || str_contains($desc, $cleanQ)) {
                $matches[] = $t;
                if (count($matches) >= $limit) {
                    break;
                }
            }
        }

        return $matches;
    }
}
