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
                $query->whereNotIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA, 'inactive'])
                      ->whereNotIn('rank', ['Inaktif', 'Aday']);
            } elseif ($filters['category'] === 'candidate') {
                $query->whereNotIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA])
                      ->where('rank', 'Aday');
            } elseif ($filters['category'] === 'inactive') {
                $query->whereNotIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA])
                      ->where(function ($q) {
                          $q->where('rank', 'Inaktif')->orWhere('status', 'inactive');
                      });
            } elseif ($filters['category'] === 'fallen') {
                $query->whereIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA]);
            }
        } elseif (!empty($filters['status'])) {
            if ($filters['status'] === 'fallen') {
                $query->whereIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA]);
            } elseif ($filters['status'] === 'active_only') {
                $query->whereNotIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA]);
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
                $query->whereIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA]);
            } elseif ($filters['status'] === 'active_only') {
                $query->whereNotIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA]);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        return $query->count();
    }

    public function truncate(): void
    {
        PlayerXp::truncate();
    }

    public function getStatistics(): array
    {
        $all = PlayerXp::all();

        $activeCharacters = $all->whereNotIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA]);
        $fallenCharacters = $all->whereIn('status', [PlayerXp::STATUS_KIA, PlayerXp::STATUS_MIA]);
        $kiaCount = $all->where('status', PlayerXp::STATUS_KIA)->count();
        $miaCount = $all->where('status', PlayerXp::STATUS_MIA)->count();

        $guildMembers = $activeCharacters->whereNotIn('rank', ['Inaktif', 'Aday'])->where('status', '!=', 'inactive');
        $candidates = $activeCharacters->where('rank', 'Aday');
        $inactives = $activeCharacters->filter(fn($c) => $c->rank === 'Inaktif' || $c->status === 'inactive');

        $totalActiveXp = $activeCharacters->sum('total_xp');
        $totalGuildXp = $guildMembers->sum('total_xp');
        $totalGrandXp = $all->sum('total_xp');
        $avgLevel = $guildMembers->count() > 0 ? round($guildMembers->avg('level'), 1) : ($activeCharacters->count() > 0 ? round($activeCharacters->avg('level'), 1) : 1);

        $topAdventurer = $activeCharacters->sortByDesc('total_xp')->first();

        return [
            'total_active_count' => $activeCharacters->count(),
            'guild_count' => $guildMembers->count(),
            'candidate_count' => $candidates->count(),
            'inactive_count' => $inactives->count(),
            'total_fallen_count' => $fallenCharacters->count(),
            'kia_count' => $kiaCount,
            'mia_count' => $miaCount,
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
