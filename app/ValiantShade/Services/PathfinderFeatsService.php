<?php

namespace App\ValiantShade\Services;

use App\Models\CharacterFeat;
use App\Models\Character;
use App\Models\CharacterInfo;
use App\Models\CharacterProfile;
use App\Models\CharacterTrait;
use App\Models\Feat;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;

class PathfinderFeatsService
{
    public function ensureInitialized(): bool
    {
        if (CharacterProfile::query()->exists()) {
            return true;
        }

        $path = storage_path('app/feats_list.json');
        if (! is_file($path)) {
            Log::warning('Feat başlangıç verileri bulunamadı.', ['path' => $path]);

            return false;
        }

        DB::transaction(fn () => $this->importRows($this->readDefaultRows()));

        return true;
    }

    public function getCharactersList(string $sortBy = 'name_asc'): EloquentCollection
    {
        $query = CharacterProfile::query()->withCount('feats');

        match ($sortBy) {
            'name_desc' => $query->orderByDesc('character_name'),
            'level_asc' => $query->orderBy('level')->orderBy('character_name'),
            'level_desc' => $query->orderByDesc('level')->orderBy('character_name'),
            'feats_asc' => $query->orderBy('feats_count')->orderBy('character_name'),
            'feats_desc' => $query->orderByDesc('feats_count')->orderBy('character_name'),
            default => $query->orderBy('character_name'),
        };

        return $query->get();
    }

    public function getCharacterDossier(string $characterName): ?array
    {
        $profile = CharacterProfile::query()
            ->where('character_name', $characterName)
            ->first();

        if (! $profile) {
            return null;
        }

        $feats = CharacterFeat::query()
            ->where('character_name', $characterName)
            ->orderBy('level')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $progression = [];
        for ($level = 1; $level <= 20; $level++) {
            $levelFeats = $feats->where('level', $level)->values();
            $slotDefinitions = [];

            if ($level % 2 === 1) {
                $slotDefinitions[] = $this->slotDefinition($level, 'standard');
            }

            $hasRacialSlot = $level === 1
                && (stripos((string) $profile->race, 'human') !== false
                    || $levelFeats->contains('slot_type', 'racial_bonus'));
            if ($hasRacialSlot) {
                $slotDefinitions[] = $this->slotDefinition($level, 'racial_bonus');
            }

            $hasClassBonusSlot = $this->hasClassBonusSlot($profile, $level)
                || $levelFeats->contains('slot_type', 'class_bonus');
            if ($hasClassBonusSlot) {
                $slotDefinitions[] = $this->slotDefinition($level, 'class_bonus');
            }

            $assignedIds = [];
            $slots = array_map(function (array $slot) use ($levelFeats, &$assignedIds): array {
                $feat = $levelFeats->first(
                    fn (CharacterFeat $candidate) => $candidate->slot_type === $slot['type']
                        && ! in_array($candidate->id, $assignedIds, true)
                );

                if ($feat) {
                    $assignedIds[] = $feat->id;
                }

                $slot['feat'] = $feat;
                $slot['is_empty'] = ! $feat;

                return $slot;
            }, $slotDefinitions);

            $progression[$level] = [
                'slots' => $slots,
                'all_feats' => $levelFeats,
                'class_feats' => $levelFeats->whereIn('slot_type', ['class_bonus', 'class_talent'])->values(),
                'extra_feats' => $levelFeats->reject(
                    fn (CharacterFeat $feat) => in_array($feat->id, $assignedIds, true)
                )->values(),
                'has_class_talent_slot' => $levelFeats->contains('slot_type', 'class_talent'),
                'has_class_bonus_slot' => $hasClassBonusSlot,
                'is_racial_slot' => $hasRacialSlot,
            ];
        }

        return [
            'profile' => $profile,
            'progression' => $progression,
            'total_feats_count' => $feats->count(),
            'class_count' => $feats->whereIn('slot_type', ['class_bonus', 'class_talent'])->count(),
            'standard_count' => $feats->where('slot_type', 'standard')->count(),
            'racial_count' => $feats->where('slot_type', 'racial_bonus')->count(),
            'calculated_bab' => $profile->bab ?: '—',
            'calculated_fort' => $profile->fort_save ?: '—',
            'calculated_ref' => $profile->ref_save ?: '—',
            'calculated_will' => $profile->will_save ?: '—',
            'hit_die' => $profile->hit_die,
        ];
    }

    public function getMasterFeatsTable(string $sortBy = 'name_asc'): array
    {
        $characters = $this->getCharactersList($sortBy);
        $featsByCharacter = CharacterFeat::query()
            ->whereIn('character_name', $characters->pluck('character_name'))
            ->get()
            ->groupBy('character_name');

        return $characters
            ->map(function (CharacterProfile $profile) use ($featsByCharacter): array {
                $feats = $featsByCharacter->get($profile->character_name, collect());

                return [
                    'rank' => $profile->rank ?: 'Üye',
                    'character_name' => $profile->character_name,
                    'player_name' => $profile->player_name ?: '—',
                    'race' => $profile->race ?: '—',
                    'class' => trim($profile->class_name.($profile->archetype ? ' ('.$profile->archetype.')' : '')),
                    'level' => $profile->level,
                    'lvl_1_standard' => $this->featNames($feats, 1, 'standard'),
                    'lvl_1_class_bonus' => $this->featNames($feats, 1, 'class_bonus'),
                    'lvl_1_racial_bonus' => $this->featNames($feats, 1, 'racial_bonus'),
                    'lvl_2_class_bonus' => $this->featNames($feats, 2, 'class_bonus'),
                    'lvl_3_standard' => $this->featNames($feats, 3, 'standard'),
                ];
            })
            ->all();
    }

    public function getCommonFeatsLibrary(): array
    {
        return Feat::query()
            ->select(['name', 'category', 'prerequisites'])
            ->orderBy('name')
            ->limit(1000)
            ->get()
            ->map(fn (Feat $feat): array => [
                'name' => $feat->name,
                'category' => $feat->category ?: '',
                'preq' => $feat->prerequisites ?: '',
            ])
            ->all();
    }

    public function storeFeat(array $data): CharacterFeat
    {
        $characterName = trim($data['character_name']);
        $level = (int) $data['level'];

        CharacterProfile::query()->where('character_name', $characterName)->firstOrFail();

        $featName = trim($data['feat_name']);
        $slotType = $data['slot_type'];

        return CharacterFeat::query()->create([
            'character_name' => $characterName,
            'level' => $level,
            'acquired_at_level' => $level,
            'slot_type' => $slotType,
            'feat_name' => $featName,
            'custom_feat_name' => $featName,
            'class_feat_name' => $data['class_feat_name'] ?? null,
            'category' => $data['category'] ?? null,
            'prerequisites' => $data['prerequisites'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_class_feat' => (bool) ($data['is_class_feat'] ?? in_array($slotType, ['class_bonus', 'class_talent'], true)),
        ]);
    }

    public function updateFeat(int $id, array $data): CharacterFeat
    {
        $feat = CharacterFeat::query()->findOrFail($id);
        $feat->fill($data);

        if (array_key_exists('feat_name', $data)) {
            $feat->custom_feat_name = trim($data['feat_name']);
        }
        if (array_key_exists('level', $data)) {
            $feat->acquired_at_level = (int) $data['level'];
        }

        $feat->save();

        return $feat;
    }

    public function deleteFeat(int $id): bool
    {
        $feat = CharacterFeat::query()->find($id);

        return $feat ? (bool) $feat->forceDelete() : false;
    }

    public function updateProfileStats(string $characterName, array $data): CharacterProfile
    {
        return DB::transaction(function () use ($characterName, $data): CharacterProfile {
            $profile = CharacterProfile::query()->where('character_name', $characterName)->firstOrFail();
            $profile->fill($data);

            $sheet = $profile->sheet_data ?? [];
            $classes = require config_path('enums/classes.php');
            foreach ($classes as $key => $class) {
                if (($class['name'] ?? null) === $profile->class_name) {
                    $sheet['classKey'] = $key;
                    break;
                }
            }
            $sheet['className'] = $profile->class_name;
            $sheet['race'] = $profile->race;
            if (isset($sheet['sheetData']) && is_array($sheet['sheetData'])) {
                $sheet['sheetData']['className'] = $profile->class_name;
                $sheet['sheetData']['race'] = $profile->race;
                if (isset($sheet['classKey'])) {
                    $sheet['sheetData']['classKey'] = $sheet['classKey'];
                }
            }
            $profile->sheet_data = $sheet;
            $profile->save();

            $characters = Character::query()->where('name', $characterName)->get();
            if ($characters->count() > 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'character_name' => __('Multiple character records have this name.'),
                ]);
            }
            if ($character = $characters->first()) {
                CharacterInfo::query()->where('character_id', $character->id)->update([
                    'race' => $profile->race,
                    'class_and_level' => $profile->class_name.' '.$profile->level,
                ]);
            }

            return $profile->fresh();
        });
    }

    public function deleteCharacter(string $characterName): bool
    {
        $profile = CharacterProfile::query()->where('character_name', $characterName)->first();
        if (! $profile) {
            return false;
        }

        DB::transaction(function () use ($profile, $characterName): void {
            CharacterFeat::withTrashed()->where('character_name', $characterName)->forceDelete();
            CharacterTrait::withTrashed()->where('character_name', $characterName)->forceDelete();
            $profile->forceDelete();
        });

        return true;
    }

    public function seedDefaults(): void
    {
        DB::transaction(function (): void {
            CharacterFeat::withTrashed()->forceDelete();
            CharacterProfile::withTrashed()->forceDelete();
            $this->importRows($this->readDefaultRows());
        });
    }

    public function getServerFeatRules(): array
    {
        return [
            'standard' => 'Standard feats are available at odd-numbered levels.',
            'racial_bonus' => 'Racial bonus feats depend on the character ancestry.',
            'class_bonus' => 'Class bonus feats depend on the character class.',
            'custom_feats_allowed' => true,
        ];
    }

    public function validateFeatSelection(string $featName, string $characterName): array
    {
        return [
            'allowed' => true,
            'message' => __('Custom feat names can be entered without a catalog match.'),
            'feat_name' => $featName,
            'character_name' => $characterName,
        ];
    }

    private function slotDefinition(int $level, string $type): array
    {
        $labels = [
            'standard' => ['name' => $level.'. Level', 'badge' => 'bg-label-primary'],
            'racial_bonus' => ['name' => $level.'. Level Racial Bonus Feat', 'badge' => 'bg-label-info'],
            'class_bonus' => ['name' => $level.'. Level Class Bonus Feat', 'badge' => 'bg-label-warning'],
        ];

        return [
            'name' => $labels[$type]['name'],
            'badge' => $labels[$type]['badge'],
            'type' => $type,
        ];
    }

    private function hasClassBonusSlot(CharacterProfile $profile, int $level): bool
    {
        $className = strtolower((string) $profile->class_name);

        if (str_contains($className, 'fighter')) {
            return $level === 1 || ($level % 2 === 0);
        }
        if (str_contains($className, 'magus')) {
            return in_array($level, [5, 11, 17], true);
        }
        if (str_contains($className, 'wizard')) {
            return in_array($level, [1, 5, 10, 15, 20], true);
        }
        if (str_contains($className, 'warpriest')) {
            return in_array($level, [3, 6, 9, 12, 15, 18], true);
        }

        return false;
    }

    private function featNames(Collection $feats, int $level, string $slotType): string
    {
        return $feats
            ->where('level', $level)
            ->where('slot_type', $slotType)
            ->pluck('feat_name')
            ->filter()
            ->implode(', ');
    }

    private function readDefaultRows(): array
    {
        $path = storage_path('app/feats_list.json');
        if (! is_file($path)) {
            throw new RuntimeException("Feat list JSON not found at {$path}.");
        }

        try {
            $rows = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Feat list JSON is invalid: '.$exception->getMessage(), previous: $exception);
        }

        if (! is_array($rows)) {
            throw new RuntimeException('Feat list JSON must contain a list of character records.');
        }

        return $rows;
    }

    private function importRows(array $rows): void
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $characterName = trim((string) ($row['Character Name'] ?? ''));
            if ($characterName === '' || $characterName === 'Character Name') {
                continue;
            }

            $classAndArchetype = trim((string) ($row['Class (Archetype)'] ?? ''));
            $className = $classAndArchetype;
            $archetype = null;
            if (preg_match('/^\s*(.*?)\s*\((.*?)\)\s*$/u', $classAndArchetype, $matches)) {
                $className = trim($matches[1]);
                $archetype = trim($matches[2]);
            }

            $profile = CharacterProfile::query()->firstOrCreate(
                ['character_name' => $characterName],
                [
                    'player_name' => trim((string) ($row['Player Name'] ?? '')) ?: null,
                    'race' => trim((string) ($row['Race'] ?? '')) ?: 'Human',
                    'class_name' => $className ?: 'Adventurer',
                    'archetype' => $archetype,
                    'level' => max(1, (int) ($row['Level'] ?? 1)),
                    'rank' => trim((string) ($row['Rank'] ?? '')) ?: 'Üye',
                ]
            );

            $sortOrder = 0;
            foreach ($row as $column => $value) {
                if (! preg_match('/^\s*(\d+)\s*\.?\s*level\b/i', (string) $column, $matches)) {
                    continue;
                }

                $featName = trim((string) $value);
                if ($featName === '' || $featName === '-' || is_numeric($value)) {
                    continue;
                }

                $columnName = strtolower((string) $column);
                $slotType = match (true) {
                    str_contains($columnName, 'racial') => 'racial_bonus',
                    str_contains($columnName, 'bonus') => 'class_bonus',
                    str_contains($columnName, 'ability'), str_contains($columnName, 'talent') => 'class_talent',
                    default => 'standard',
                };
                $level = (int) $matches[1];

                CharacterFeat::query()->firstOrCreate(
                    [
                        'character_name' => $characterName,
                        'level' => $level,
                        'feat_name' => $featName,
                        'class_feat_name' => (string) $column,
                    ],
                    [
                        'custom_feat_name' => $featName,
                        'acquired_at_level' => $level,
                        'slot_type' => $slotType,
                        'notes' => 'Imported from column: '.$column,
                        'sort_order' => $sortOrder++,
                        'is_class_feat' => in_array($slotType, ['class_bonus', 'class_talent'], true),
                    ]
                );
            }
        }
    }
}
