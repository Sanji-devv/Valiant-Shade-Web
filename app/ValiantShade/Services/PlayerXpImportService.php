<?php

namespace App\ValiantShade\Services;

use App\Models\Character;
use App\Models\PlayerXp;
use App\Models\User;
use App\ValiantShade\Exceptions\PlayerXpImportException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlayerXpImportService
{
    public function import(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new PlayerXpImportException(__('The XP file could not be read: :path', ['path' => $path]));
        }

        $json = file_get_contents($path);
        if ($json === false) {
            throw new PlayerXpImportException(__('The XP file could not be read: :path', ['path' => $path]));
        }
        $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows) || ! array_is_list($rows) || $rows === []) {
            throw new PlayerXpImportException(__('The XP file is empty or is not a valid list of records.'));
        }

        $records = [];
        foreach ($rows as $index => $row) {
            if (! is_array($row) || ! array_key_exists('İsim', $row) || ! array_key_exists('İsim_2', $row)) {
                throw new PlayerXpImportException(__('Invalid row in the XP file: :row.', ['row' => $index + 1]));
            }
            foreach ([false, true] as $fallen) {
                $name = trim((string) ($row[$fallen ? 'İsim_2' : 'İsim'] ?? ''));
                if ($name === '' || $name === 'İsim') {
                    continue;
                }
                $player = trim((string) ($row[$fallen ? 'Oyuncu_2' : 'Oyuncu'] ?? ''));
                $cause = strtoupper(trim((string) ($row['Sebep'] ?? '')));
                if ($fallen && ! in_array($cause, ['KIA', 'MIA'], true)) {
                    throw new PlayerXpImportException(__(':name: invalid KIA/MIA reason.', ['name' => $name]));
                }
                $rank = trim((string) ($row['Rütbe'] ?? '')) ?: 'Aday';
                $total = $this->number($row, $fallen ? 'XP_2' : 'XP', $index);
                $level = $fallen ? PlayerXp::calculateLevelFromXp($total) : max(1, $this->number($row, 'Level', $index));
                $data = [
                    'character_name' => $name,
                    'player_name' => $player !== '' ? $player : null,
                    'status' => $fallen ? strtolower($cause) : match ($rank) {
                        'Inaktif' => 'inactive',
                        'Gezgin' => 'traveler',
                        default => 'active',
                    },
                    'rank' => $fallen ? PlayerXp::calculateRankFromLevelAndSessions($level) : $rank,
                    'level' => $level,
                    'sessions_count' => $this->number($row, $fallen ? 'Oyun' : 'Görev', $index),
                    'rank_xp' => $fallen ? 0 : $this->number($row, 'Rütbe_2', $index),
                    'mission_xp' => $this->number($row, $fallen ? 'Görev_3' : 'Görev_2', $index),
                    'report_xp' => $this->number($row, $fallen ? 'Rapor_2' : 'Rapor', $index),
                    'bailiff_xp' => $fallen ? 0 : $this->number($row, 'Bailiff', $index),
                    'extra_xp' => $fallen ? 0 : $this->number($row, 'EX', $index),
                    'bonus_xp' => $fallen ? 0 : $this->number($row, 'Wiki', $index),
                    'total_xp' => $total,
                    'inactive_threshold_xp' => $fallen ? null : $this->number($row, 'Inaktif', $index),
                    'reason' => $fallen ? $cause : null,
                ];
                $key = $this->key($name)."\0".$this->key($player)."\0".($fallen ? 'fallen' : 'roster');
                if (isset($records[$key]) && $records[$key] !== $data) {
                    throw new PlayerXpImportException(__(':name: duplicate records have conflicting values.', ['name' => $name]));
                }
                $records[$key] = $data;
            }
        }
        if ($records === []) {
            throw new PlayerXpImportException(__('No characters to import were found in the XP file.'));
        }

        if (! Schema::hasTable('quest_report_xp_distributions')) {
            throw new PlayerXpImportException(__('The quest XP distribution table is missing. Run database migrations before modifying XP records.'));
        }

        return DB::transaction(function () use ($records) {
            $users = User::all()->groupBy(fn ($user) => $this->key($user->name));
            $characters = Character::with('user')->get()->groupBy(fn ($character) => $this->key($character->name));
            $existing = PlayerXp::all()->groupBy(fn ($xp) => $this->key($xp->character_name));
            $sourceNames = collect($records)->groupBy(fn ($record) => $this->key($record['character_name']));
            $awardedIds = [];
            foreach (DB::table('quest_report_xp_distributions')->lockForUpdate()->pluck('awards') as $json) {
                try {
                    $awards = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    throw new PlayerXpImportException(__('The quest XP distribution record is invalid.'));
                }
                if (! is_array($awards) || ! array_is_list($awards)) {
                    throw new PlayerXpImportException(__('The quest XP distribution record is invalid.'));
                }
                foreach ($awards as $award) {
                    if (! is_array($award) || ! isset($award['player_xp_id'])
                        || filter_var($award['player_xp_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                        throw new PlayerXpImportException(__('The quest XP distribution record is invalid.'));
                    }
                    $awardedIds[(int) $award['player_xp_id']] = true;
                }
            }

            $unmatched = [];
            foreach ($records as $data) {
                $nameKey = $this->key($data['character_name']);
                $playerKey = $this->key($data['player_name'] ?? '');
                $owners = $playerKey === '' ? collect() : $users->get($playerKey, collect());
                $chars = $characters->get($nameKey, collect());
                $matchingChars = $chars->filter(fn ($char) => $this->key($char->user?->name ?? '') === $playerKey && $playerKey !== '');
                if ($matchingChars->count() === 1) {
                    $owner = $matchingChars->first()->user;
                } elseif ($chars->count() === 1 && $sourceNames[$nameKey]->count() === 1
                    && ($this->isUnknown($data['player_name']) || $this->isUnknown($chars->first()->user?->name))) {
                    $owner = $chars->first()->user;
                } else {
                    $owner = $chars->isEmpty() && $owners->count() === 1 ? $owners->first() : null;
                }

                $candidates = $existing->get($nameKey, collect());
                $matches = $candidates->filter(fn ($xp) => $this->key($xp->player_name ?? '') === $playerKey
                    && (in_array($xp->status, ['kia', 'mia'], true) === in_array($data['status'], ['kia', 'mia'], true)));
                if ($matches->isEmpty() && $owner) {
                    $matches = $candidates->filter(fn ($xp) => (int) $xp->user_id === (int) $owner->id
                        && (in_array($xp->status, ['kia', 'mia'], true) === in_array($data['status'], ['kia', 'mia'], true)));
                }
                if ($matches->isEmpty() && $candidates->count() === 1 && $sourceNames[$nameKey]->count() === 1
                    && ($candidates->first()->user_id === null || ($owner && (int) $candidates->first()->user_id === (int) $owner->id))) {
                    $matches = $candidates;
                }
                if ($matches->count() > 1) {
                    throw new PlayerXpImportException(__(':name: more than one XP record matches.', ['name' => $data['character_name']]));
                }
                $xp = $matches->first();
                if ($xp && isset($awardedIds[$xp->id])) {
                    throw new PlayerXpImportException(__(':name: quest report XP has been distributed; the import cannot proceed safely.', ['name' => $data['character_name']]));
                }
                if ($xp && $xp->user_id && $owner && (int) $xp->user_id !== (int) $owner->id) {
                    throw new PlayerXpImportException(__(':name: the XP record and character belong to different users.', ['name' => $data['character_name']]));
                }
                $data['user_id'] = $owner?->id ?? $xp?->user_id;
                if (! $data['user_id']) {
                    $unmatched[] = $data['character_name'].' ('.($data['player_name'] ?? 'Unknown').')';
                }
                if ($xp) {
                    $xp->update($data);
                } else {
                    $xp = PlayerXp::create($data);
                    $existing->put($nameKey, $candidates->push($xp));
                }
            }

            return ['imported' => count($records), 'unmatched' => $unmatched];
        });
    }

    private function number(array $row, string $key, int $index): int
    {
        $value = $row[$key] ?? 0;
        if ($value === '') {
            return 0;
        }
        if (! is_numeric($value) || (float) $value < 0) {
            throw new PlayerXpImportException(__('Invalid value in the XP file: row :row, :column.', ['row' => $index + 1, 'column' => $key]));
        }

        return (int) round((float) $value);
    }

    private function key(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    private function isUnknown(?string $name): bool
    {
        $name = $this->key($name ?? '');

        return $name === '' || $name === '?' || $name === 'valiant adventurer' || preg_match('/^unknown(?:\s|$)/u', $name) === 1;
    }
}
