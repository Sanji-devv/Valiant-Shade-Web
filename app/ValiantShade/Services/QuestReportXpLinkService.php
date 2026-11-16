<?php

namespace App\ValiantShade\Services;

use App\Models\Character;
use App\Models\PlayerXp;
use App\Models\Quest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestReportXpLinkService
{
    public function indexes(): array
    {
        return [
            'characters' => Character::get(['id', 'user_id', 'name'])->groupBy(fn ($item) => $this->key($item->name)),
            'players' => PlayerXp::get(['id', 'user_id', 'character_name'])->groupBy(fn ($item) => $this->key($item->character_name)),
        ];
    }

    public function sync(Quest $quest, array $report, string $state, array $indexes, array $awards = []): array
    {
        $reporter = trim((string) ($report['reporter'] ?? ''));
        $others = $report['other_players'] ?? [];
        if ($reporter === '' || ! is_array($others)
            || ! is_numeric($report['avg_reward'] ?? null) || ! is_numeric($report['rapor_cut'] ?? null)) {
            throw ValidationException::withMessages(['report' => __('The report has invalid participant or XP data.')]);
        }

        if ($state === 'distributed') {
            foreach ($awards as $award) {
                if (! is_array($award) || ! is_string($award['name'] ?? null)
                    || ! array_key_exists('character_id', $award) || ! isset($award['player_xp_id'])
                    || filter_var($award['player_xp_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false
                    || ($award['character_id'] !== null && filter_var($award['character_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false)
                    || ! is_numeric($award['quest_xp'] ?? null) || ! is_numeric($award['report_xp'] ?? null)
                    || $award['quest_xp'] < 0 || $award['report_xp'] < 0) {
                    throw ValidationException::withMessages(['report' => __('The quest XP distribution record is invalid.')]);
                }
            }
        }
        $awardsByName = collect($awards)->keyBy(fn ($award) => $this->key($award['name']));
        if ($state === 'distributed' && $awardsByName->count() !== count($awards)) {
            throw ValidationException::withMessages(['report' => __('The quest XP distribution record is invalid.')]);
        }
        $names = array_merge([$reporter], $others);
        $entries = [];
        $unresolved = [];
        $now = now();
        foreach ($names as $index => $rawName) {
            if (! is_string($rawName)) {
                throw ValidationException::withMessages(['report' => __('The report has invalid participant or XP data.')]);
            }
            $name = trim($rawName);
            $key = $this->key($name);
            if ($key === '' || in_array($key, ['-', 'yok'], true)) {
                continue;
            }
            if (isset($entries[$key])) {
                continue;
            }
            $character = $indexes['characters']->get($key, collect());
            $player = $indexes['players']->get($key, collect());
            $char = $character->count() === 1 ? $character->first() : null;
            $xp = $player->count() === 1 ? $player->first() : null;
            if ($char && $xp && $xp->user_id && (int) $char->user_id !== (int) $xp->user_id) {
                $char = null;
                $xp = null;
            }
            $award = $state === 'distributed' ? $awardsByName->get($key) : null;
            if ($state === 'distributed' && ! $award) {
                throw ValidationException::withMessages(['report' => __('The quest XP distribution record is invalid.')]);
            }
            $characterId = $award ? $award['character_id'] : $char?->id;
            $playerId = $award ? $award['player_xp_id'] : $xp?->id;
            if (! $characterId || ! $playerId) {
                $unresolved[] = $name;
            }
            $entries[$key] = [
                'quest_id' => $quest->id,
                'character_id' => $characterId,
                'player_xp_id' => $playerId,
                'participant_name' => $name,
                'participant_key' => $key,
                'quest_xp' => $award['quest_xp'] ?? $report['avg_reward'],
                'report_xp' => $award['report_xp'] ?? ($index === 0 ? $report['rapor_cut'] : 0),
                'state' => $state,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($entries === [] || ($state === 'distributed' && count($entries) !== count($awards))) {
            throw ValidationException::withMessages(['report' => __('The report has invalid participant or XP data.')]);
        }

        DB::table('quest_report_xp_entries')->upsert(
            array_values($entries),
            ['quest_id', 'participant_key'],
            ['character_id', 'player_xp_id', 'participant_name', 'quest_xp', 'report_xp', 'state', 'updated_at']
        );
        DB::table('quest_report_xp_entries')->where('quest_id', $quest->id)
            ->whereNotIn('participant_key', array_keys($entries))->delete();

        return ['entries' => count($entries), 'unresolved' => $unresolved];
    }

    private function key(string $name): string
    {
        return mb_strtolower(trim($name));
    }
}
