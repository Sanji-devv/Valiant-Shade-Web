<?php

namespace App\ValiantShade\Repositories;

use App\Models\PlayerXp;
use App\ValiantShade\Contracts\PlayerXpRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PlayerXpRepository implements PlayerXpRepositoryInterface
{
    public function all(array $filters = []): Collection
    {
        $query = PlayerXp::query();

        if (!empty($filters['category'])) {
            if ($filters['category'] === 'guild') {
                $query->whereNotIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA, PlayerXp::STATUS_RETIRED, 'inactive'])
                      ->whereNotIn('rank', ['Inaktif', 'Aday']);
            } elseif ($filters['category'] === 'candidate') {
                $query->whereNotIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA, PlayerXp::STATUS_RETIRED])
                      ->where('rank', 'Aday');
            } elseif ($filters['category'] === 'inactive') {
                $query->whereNotIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA, PlayerXp::STATUS_RETIRED])
                      ->where(function ($q) {
                          $q->where('rank', 'Inaktif')->orWhere('status', 'inactive');
                      });
            } elseif ($filters['category'] === 'fallen') {
                $query->whereIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA, PlayerXp::STATUS_RETIRED]);
            }
        } elseif (!empty($filters['status'])) {
            if ($filters['status'] === 'fallen') {
                $query->whereIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA, PlayerXp::STATUS_RETIRED]);
            } elseif ($filters['status'] === 'active_only') {
                $query->whereNotIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA, PlayerXp::STATUS_RETIRED]);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['hide_inactive'])) {
            $query->where('rank', '!=', 'Inaktif')->where('status', '!=', 'inactive');
        }

        if (!empty($filters['hide_candidates'])) {
            $query->where('rank', '!=', 'Aday');
        }

        if (!empty($filters['rank'])) {
            $query->where('rank', $filters['rank']);
        }

        if (!empty($filters['level'])) {
            $query->where('level', (int) $filters['level']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('character_name', 'like', $term)
                  ->orWhere('player_name', 'like', $term)
                  ->orWhere('reason', 'like', $term);
            });
        }

        $sortBy = $filters['sort_by'] ?? 'total_xp';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'id', 'character_name', 'player_name', 'status', 'rank',
            'level', 'sessions_count', 'total_xp', 'mission_xp', 'report_xp'
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('total_xp', 'desc');
        }

        return $query->get();
    }

    public function find(int $id): ?PlayerXp
    {
        return PlayerXp::find($id);
    }

    public function create(array $data): PlayerXp
    {
        return PlayerXp::create($data);
    }

    public function update(int $id, array $data): PlayerXp
    {
        $record = PlayerXp::findOrFail($id);
        $record->update($data);
        return $record;
    }

    public function delete(int $id): bool
    {
        $record = PlayerXp::find($id);
        return $record ? (bool) $record->delete() : false;
    }

    public function count(array $filters = []): int
    {
        $query = PlayerXp::query();

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'fallen') {
                $query->whereIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA, PlayerXp::STATUS_RETIRED]);
            } elseif ($filters['status'] === 'active_only') {
                $query->whereNotIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA, PlayerXp::STATUS_RETIRED]);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        return $query->count();
    }

    public function truncate(): void
    {
        PlayerXp::withTrashed()->forceDelete();
    }

    public function getStatistics(): array
    {
        $kiaCount = PlayerXp::where('status', PlayerXp::STATUS_KIA)->count();
        $miaCount = PlayerXp::where('status', PlayerXp::STATUS_MIA)->count();
        $retiredCount = PlayerXp::where('status', PlayerXp::STATUS_RETIRED)->count();
        $fallenCount = $kiaCount + $miaCount + $retiredCount;

        $activeQuery = PlayerXp::whereNotIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA, PlayerXp::STATUS_RETIRED]);
        $totalActiveCount = (clone $activeQuery)->count();
        $totalActiveXp = (int) (clone $activeQuery)->sum('total_xp');

        $guildQuery = (clone $activeQuery)->whereNotIn('rank', ['Inaktif', 'Aday'])->where('status', '!=', 'inactive');
        $guildCount = (clone $guildQuery)->count();
        $totalGuildXp = (int) (clone $guildQuery)->sum('total_xp');
        $avgLevel = $guildCount > 0 ? round((float) (clone $guildQuery)->avg('level'), 1) : 1.0;

        $candidateCount = (clone $activeQuery)->where('rank', 'Aday')->count();
        $inactiveCount = (clone $activeQuery)->where(function ($q) {
            $q->where('rank', 'Inaktif')->orWhere('status', 'inactive');
        })->count();

        $totalGrandXp = (int) PlayerXp::sum('total_xp');
        $topAdventurer = (clone $activeQuery)->orderBy('total_xp', 'desc')->first(['character_name', 'player_name', 'level', 'total_xp']);

        return [
            'total_active_count' => $totalActiveCount,
            'guild_count' => $guildCount,
            'candidate_count' => $candidateCount,
            'inactive_count' => $inactiveCount,
            'total_fallen_count' => $fallenCount,
            'kia_count' => $kiaCount,
            'mia_count' => $miaCount,
            'retired_count' => $retiredCount,
            'total_active_xp' => $totalActiveXp,
            'total_guild_xp' => $totalGuildXp,
            'total_grand_xp' => $totalGrandXp,
            'avg_level' => $avgLevel,
            'top_adventurer' => $topAdventurer ? [
                'name' => $topAdventurer->character_name,
                'player' => $topAdventurer->player_name,
                'level' => $topAdventurer->level,
                'xp' => $topAdventurer->total_xp,
            ] : null,
        ];
    }
}
