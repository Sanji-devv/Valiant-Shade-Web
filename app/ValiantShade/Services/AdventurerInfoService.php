<?php

namespace App\ValiantShade\Services;

use App\Models\GuildBailiff;
use App\Models\GuildSetting;
use App\Models\PlayerXp;
use Illuminate\Support\Collection;

class AdventurerInfoService
{
    /**
     * Static Master Sheet Guild Level Data.
     */
    public function getGuildLevelInfo(): array
    {
        $level = 12;
        $nextLevelPoints = 546;
        $currentPoints = 454;

        // Attempt to read from DB if table exists, otherwise static default
        try {
            if (class_exists(GuildSetting::class)) {
                $level = (int) GuildSetting::get('guild_level', 12);
                $nextLevelPoints = (int) GuildSetting::get('next_level_points', 546);
                $currentPoints = (int) GuildSetting::get('current_level_points', 454);
            }
        } catch (\Throwable $e) {
            // Fall back to static master values
        }

        $totalLevelTarget = $currentPoints + $nextLevelPoints;
        $progressPercent = $totalLevelTarget > 0 ? round(($currentPoints / $totalLevelTarget) * 100, 1) : 45.4;

        return [
            'level' => $level,
            'next_level_points' => $nextLevelPoints,
            'current_points' => $currentPoints,
            'progress_percent' => $progressPercent,
        ];
    }

    /**
     * Static Bailiff Council (7 Officers from Columns S-U in Master Sheet).
     */
    public function getBailiffCouncil(): Collection
    {
        // Try DB first, fallback to static screenshot data
        try {
            if (class_exists(GuildBailiff::class) && GuildBailiff::count() > 0) {
                return GuildBailiff::orderBy('order_index', 'asc')->get();
            }
        } catch (\Throwable $e) {
            // Fallback to static
        }

        return $this->getStaticBailiffs();
    }

    /**
     * Get static Bailiff collection.
     */
    public function getStaticBailiffs(): Collection
    {
        return collect([
            (object) [
                'id' => 1,
                'character_name' => 'Thoma Quickfire',
                'role' => 'Kahraman Kaynakları',
                'assigned_date' => '14/7',
                'status' => 'active',
                'order_index' => 1,
            ],
            (object) [
                'id' => 2,
                'character_name' => 'Temran',
                'role' => 'Envanter',
                'assigned_date' => '13/9',
                'status' => 'active',
                'order_index' => 2,
            ],
            (object) [
                'id' => 3,
                'character_name' => 'Mirang',
                'role' => 'Lonca Temsilcisi',
                'assigned_date' => '27/8',
                'status' => 'active',
                'order_index' => 3,
            ],
            (object) [
                'id' => 4,
                'character_name' => 'Zandar',
                'role' => 'Questmaster',
                'assigned_date' => '25/8',
                'status' => 'active',
                'order_index' => 4,
            ],
            (object) [
                'id' => 5,
                'character_name' => 'Mart',
                'role' => 'Arşivci',
                'assigned_date' => '27/8',
                'status' => 'active',
                'order_index' => 5,
            ],
            (object) [
                'id' => 6,
                'character_name' => 'Yanek',
                'role' => 'Disiplin',
                'assigned_date' => '3/7',
                'status' => 'active',
                'order_index' => 6,
            ],
            (object) [
                'id' => 7,
                'character_name' => 'Bart',
                'role' => 'Oathkeeper',
                'assigned_date' => '13/1',
                'status' => 'active',
                'order_index' => 7,
            ],
        ]);
    }

    /**
     * Static Rank Pools and Averages table matching Google Sheet screenshot.
     */
    public function getRankPoolsAndAverages(): array
    {
        $pools = [
            [
                'rank' => 'Bailiff',
                'pool' => 176112.50,
                'average' => 35222.50,
                'members_count' => 5,
                'color' => 'primary',
                'badge' => 'bg-label-primary',
            ],
            [
                'rank' => 'Uzman',
                'pool' => 1972006.77,
                'average' => 98600.34,
                'members_count' => 20,
                'color' => 'success',
                'badge' => 'bg-label-success',
            ],
            [
                'rank' => 'Kıdemli',
                'pool' => 512436.29,
                'average' => 22279.84,
                'members_count' => 23,
                'color' => 'warning',
                'badge' => 'bg-label-warning',
            ],
            [
                'rank' => 'Üye',
                'pool' => 29091.43,
                'average' => 1385.31,
                'members_count' => 21,
                'color' => 'info',
                'badge' => 'bg-label-info',
            ],
            [
                'rank' => 'Clerk',
                'pool' => 201300.00,
                'average' => 3300.00,
                'members_count' => 61,
                'color' => 'secondary',
                'badge' => 'bg-label-secondary',
            ],
            [
                'rank' => 'Aday',
                'pool' => 0.00,
                'average' => 0.00,
                'members_count' => 280,
                'color' => 'dark',
                'badge' => 'bg-label-dark',
            ],
            [
                'rank' => 'Inaktif/Gezgin',
                'pool' => 571095.51,
                'average' => 5239.41,
                'members_count' => 229,
                'color' => 'danger',
                'badge' => 'bg-label-danger',
            ],
        ];

        $totalPool = array_sum(array_column($pools, 'pool'));

        return [
            'rows' => $pools,
            'total_pool' => $totalPool,
            'total_members' => array_sum(array_column($pools, 'members_count')),
        ];
    }

    /**
     * Static Adventurers Live Roster (Columns A-E in screenshot).
     */
    public function getAdventurersRoster(array $filters = []): Collection
    {
        // Fast Progression Level Thresholds
        $thresholds = [
            0 => 0, 1 => 0, 2 => 1300, 3 => 3300, 4 => 6000, 5 => 10000,
            6 => 15000, 7 => 23000, 8 => 34000, 9 => 50000, 10 => 71000,
            11 => 105000, 12 => 145000, 13 => 210000, 14 => 295000, 15 => 425000,
            16 => 600000, 17 => 850000, 18 => 1200000, 19 => 1700000, 20 => 2400000,
        ];

        try {
            if (class_exists(\App\Models\Character::class) && \App\Models\Character::count() > 0) {
                $query = \App\Models\Character::whereIn('status', ['ACTIVE', 'INACTIVE', 'TRAVELER'])
                    ->with(['rank', 'user'])
                    ->orderByDesc('total_xp');

                if (!empty($filters['search'])) {
                    $term = $filters['search'];
                    $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                    $query->where(function ($q) use ($term, $like) {
                        $q->where('name', $like, "%{$term}%")
                          ->orWhereHas('user', function ($uq) use ($term, $like) {
                              $uq->where('display_name', $like, "%{$term}%")
                                 ->orWhere('name', $like, "%{$term}%")
                                 ->orWhere('real_name', $like, "%{$term}%");
                          });
                    });
                }

                if (!empty($filters['rank'])) {
                    $query->whereHas('rank', function ($rq) use ($filters) {
                        $rq->where('name', $filters['rank']);
                    });
                }

                if (!empty($filters['level'])) {
                    $query->where('current_level', (int) $filters['level']);
                }

                $chars = $query->get();
                if ($chars->isNotEmpty()) {
                    $items = collect();
                    foreach ($chars as $idx => $c) {
                        $xp = (float) $c->total_xp;
                        $level = max(1, (int) $c->current_level);
                        $rankName = $c->rank?->name ?? 'Aday';
                        $currentBase = $thresholds[$level] ?? 0;
                        $nextTarget = $thresholds[$level + 1] ?? ($currentBase + 100000);
                        $span = max(1, $nextTarget - $currentBase);
                        $currentInLevel = max(0, $xp - $currentBase);
                        $percent = min(100, round(($currentInLevel / $span) * 100, 1));
                        $remaining = max(0, $nextTarget - $xp);

                        $badgeColor = match ($rankName) {
                            'Bailiff' => 'bg-label-primary',
                            'Uzman' => 'bg-label-success',
                            'Kıdemli' => 'bg-label-warning',
                            'Üye' => 'bg-label-info',
                            'Clerk' => 'bg-label-secondary',
                            'Inaktif' => 'bg-label-danger',
                            'Gezgin' => 'bg-label-dark',
                            default => 'bg-label-secondary',
                        };

                        $items->push((object) [
                            'id' => $c->id,
                            'character_name' => $c->name,
                            'player_name' => $c->user?->display_name ?? $c->user?->name ?? 'Adventurer',
                            'rank' => $rankName,
                            'level' => $level,
                            'effective_level' => $level,
                            'total_xp' => $xp,
                            'sessions_count' => $c->questParticipants()->count() ?: 1,
                            'rank_badge' => $badgeColor,
                            'progress_percent' => $percent,
                            'next_level_xp' => $nextTarget,
                            'progress_label' => number_format($xp) . ' / ' . number_format($nextTarget) . ' XP',
                            'xp_progress' => [
                                'current_xp' => $xp,
                                'current_level_base' => $currentBase,
                                'next_level_xp' => $nextTarget,
                                'current_in_level' => $currentInLevel,
                                'needed_for_level' => $span,
                                'percent' => $percent,
                                'remaining_xp' => $remaining,
                                'is_max' => false,
                            ],
                        ]);
                    }
                    return $items;
                }
            }
        } catch (\Throwable $e) {
            // Fallback to static roster below
        }

        $rawRoster = [
            ['character_name' => 'Bart',            'total_xp' => 400287, 'rank' => 'Uzman',   'level' => 14, 'player_name' => 'Turgut'],
            ['character_name' => 'Warlock',         'total_xp' => 175839, 'rank' => 'Uzman',   'level' => 12, 'player_name' => 'Mete'],
            ['character_name' => 'Sango',           'total_xp' => 165502, 'rank' => 'Uzman',   'level' => 12, 'player_name' => 'Umut'],
            ['character_name' => 'Duke',            'total_xp' => 161539, 'rank' => 'Uzman',   'level' => 12, 'player_name' => 'Erdem'],
            ['character_name' => 'Baulbazahr',      'total_xp' => 155905, 'rank' => 'Uzman',   'level' => 12, 'player_name' => 'Emre'],
            ['character_name' => 'Azeriah',         'total_xp' => 152353, 'rank' => 'Uzman',   'level' => 12, 'player_name' => 'Berkay'],
            ['character_name' => 'Taro',            'total_xp' => 147407, 'rank' => 'Uzman',   'level' => 12, 'player_name' => 'Caner'],
            ['character_name' => 'Thoma Quickfire', 'total_xp' => 147381, 'rank' => 'Uzman',   'level' => 12, 'player_name' => 'Yiğit'],
            ['character_name' => 'Kerrik',          'total_xp' => 145624, 'rank' => 'Uzman',   'level' => 12, 'player_name' => 'Onur'],
            ['character_name' => 'Yanek',           'total_xp' => 144721, 'rank' => 'Uzman',   'level' => 11, 'player_name' => 'Sinan'],
            ['character_name' => 'Raska',           'total_xp' => 144155, 'rank' => 'Uzman',   'level' => 11, 'player_name' => 'Serkan'],
            ['character_name' => 'Mordo Mado',      'total_xp' => 141145, 'rank' => 'Uzman',   'level' => 11, 'player_name' => 'Burak'],
            ['character_name' => 'Dred\'nar',       'total_xp' => 135860, 'rank' => 'Uzman',   'level' => 11, 'player_name' => 'Cihan'],
            ['character_name' => 'Ezekiel',         'total_xp' => 134889, 'rank' => 'Uzman',   'level' => 11, 'player_name' => 'Barış'],
            ['character_name' => 'Iivaria',         'total_xp' => 130882, 'rank' => 'Uzman',   'level' => 11, 'player_name' => 'Alper'],
            ['character_name' => 'Arelion Vane',    'total_xp' => 129141, 'rank' => 'Uzman',   'level' => 11, 'player_name' => 'Oğuzhan'],
            ['character_name' => 'Mirang',          'total_xp' => 116768, 'rank' => 'Uzman',   'level' => 11, 'player_name' => 'Emirhan'],
            ['character_name' => 'Da\'ak T\'aur',   'total_xp' => 111412, 'rank' => 'Uzman',   'level' => 11, 'player_name' => 'Altuğ'],
            ['character_name' => 'Zandar',          'total_xp' => 110372, 'rank' => 'Uzman',   'level' => 11, 'player_name' => 'Gökhan'],
            ['character_name' => 'Mart',            'total_xp' => 105113, 'rank' => 'Uzman',   'level' => 11, 'player_name' => 'Mert'],
            ['character_name' => 'Sangruiel Downb', 'total_xp' => 104272, 'rank' => 'Uzman',   'level' => 10, 'player_name' => 'Deniz'],
            ['character_name' => 'Belle',           'total_xp' => 103904, 'rank' => 'Uzman',   'level' => 10, 'player_name' => 'Elif'],
            ['character_name' => 'Noodle',          'total_xp' => 71000,  'rank' => 'Kıdemli', 'level' => 10, 'player_name' => 'Kaan'],
            ['character_name' => 'Yusari',          'total_xp' => 71000,  'rank' => 'Kıdemli', 'level' => 10, 'player_name' => 'Efe'],
            ['character_name' => 'Desen',           'total_xp' => 50000,  'rank' => 'Kıdemli', 'level' => 9,  'player_name' => 'Tuğçe'],
            ['character_name' => 'Raphael',         'total_xp' => 50000,  'rank' => 'Kıdemli', 'level' => 9,  'player_name' => 'Selin'],
            ['character_name' => 'Temran',          'total_xp' => 50000,  'rank' => 'Kıdemli', 'level' => 9,  'player_name' => 'Kemal'],
            ['character_name' => 'Bisco',           'total_xp' => 49573,  'rank' => 'Kıdemli', 'level' => 8,  'player_name' => 'Doruk'],
            ['character_name' => 'Dante',           'total_xp' => 44764,  'rank' => 'Inaktif', 'level' => 8,  'player_name' => 'Arda'],
            ['character_name' => 'Argus',           'total_xp' => 43487,  'rank' => 'Gezgin',  'level' => 8,  'player_name' => 'Cem'],
            ['character_name' => 'DC',              'total_xp' => 39063,  'rank' => 'Gezgin',  'level' => 8,  'player_name' => 'Can'],
            ['character_name' => 'Dabria',          'total_xp' => 37336,  'rank' => 'Inaktif', 'level' => 8,  'player_name' => 'Buse'],
        ];

        $items = collect();
        foreach ($rawRoster as $idx => $r) {
            $xp = $r['total_xp'];
            $level = $r['level'];

            $currentBase = $thresholds[$level] ?? 0;
            $nextTarget = $thresholds[$level + 1] ?? ($currentBase + 100000);
            $span = max(1, $nextTarget - $currentBase);
            $currentInLevel = max(0, $xp - $currentBase);
            $percent = min(100, round(($currentInLevel / $span) * 100, 1));
            $remaining = max(0, $nextTarget - $xp);

            $badgeColor = match ($r['rank']) {
                'Bailiff' => 'bg-label-primary',
                'Uzman' => 'bg-label-success',
                'Kıdemli' => 'bg-label-warning',
                'Üye' => 'bg-label-info',
                'Clerk' => 'bg-label-secondary',
                'Inaktif' => 'bg-label-danger',
                'Gezgin' => 'bg-label-dark',
                default => 'bg-label-secondary',
            };

            $obj = (object) [
                'id' => $idx + 1,
                'character_name' => $r['character_name'],
                'player_name' => $r['player_name'],
                'rank' => $r['rank'],
                'level' => $level,
                'effective_level' => $level,
                'total_xp' => $xp,
                'sessions_count' => 6,
                'rank_badge' => $badgeColor,
                'progress_percent' => $percent,
                'next_level_xp' => $nextTarget,
                'progress_label' => number_format($xp) . ' / ' . number_format($nextTarget) . ' XP',
                'xp_progress' => [
                    'current_xp' => $xp,
                    'current_level_base' => $currentBase,
                    'next_level_xp' => $nextTarget,
                    'current_in_level' => $currentInLevel,
                    'needed_for_level' => $span,
                    'percent' => $percent,
                    'remaining_xp' => $remaining,
                    'is_max' => false,
                ],
            ];

            // Filter search
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                if (!str_contains(strtolower($obj->character_name), $search) && !str_contains(strtolower($obj->player_name ?? ''), $search)) {
                    continue;
                }
            }

            // Filter rank
            if (!empty($filters['rank']) && $obj->rank !== $filters['rank']) {
                continue;
            }

            // Filter level
            if (!empty($filters['level']) && (int) $obj->level !== (int) $filters['level']) {
                continue;
            }

            $items->push($obj);
        }

        return $items;
    }

    /**
     * Static Allowances (SP) List (Columns G-H in screenshot).
     */
    public function getAllowances(array $filters = []): array
    {
        try {
            if (class_exists(\App\Models\Character::class) && \App\Models\Character::where('allowance_sp', '>', 0)->count() > 0) {
                $query = \App\Models\Character::where('allowance_sp', '>', 0)
                    ->with('rank')
                    ->orderByDesc('allowance_sp');

                if (!empty($filters['search'])) {
                    $term = $filters['search'];
                    $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                    $query->where('name', $like, "%{$term}%");
                }

                if (!empty($filters['rank'])) {
                    $query->whereHas('rank', function ($rq) use ($filters) {
                        $rq->where('name', $filters['rank']);
                    });
                }

                if (!empty($filters['level'])) {
                    $query->where('current_level', (int) $filters['level']);
                }

                $chars = $query->get();
                if ($chars->isNotEmpty()) {
                    $items = collect();
                    $totalSp = 0;
                    foreach ($chars as $c) {
                        $sp = (float) $c->allowance_sp;
                        $totalSp += $sp;
                        $rankName = $c->rank?->name ?? 'Aday';
                        $level = max(1, (int) $c->current_level);

                        $badge = match ($rankName) {
                            'Uzman' => 'bg-label-success',
                            'Kıdemli' => 'bg-label-warning',
                            default => 'bg-label-secondary',
                        };

                        $items->push((object) [
                            'character_name' => $c->name,
                            'allowance_sp' => $sp,
                            'gp_equivalent' => round($sp / 10, 1),
                            'rank' => $rankName,
                            'level' => $level,
                            'effective_level' => $level,
                            'badge' => $badge,
                        ]);
                    }

                    $count = $items->count();
                    $averageSp = $count > 0 ? round($totalSp / $count, 1) : 0;
                    $topEarner = $items->first();

                    return [
                        'items' => $items,
                        'total_sp' => $totalSp,
                        'total_gp' => round($totalSp / 10, 1),
                        'average_sp' => $averageSp,
                        'count' => $count,
                        'top_earner' => $topEarner ? [
                            'name' => $topEarner->character_name,
                            'allowance_sp' => $topEarner->allowance_sp,
                            'rank' => $topEarner->rank,
                            'level' => $topEarner->effective_level,
                        ] : [
                            'name' => 'Bart',
                            'allowance_sp' => 991787,
                            'rank' => 'Uzman',
                            'level' => 14,
                        ],
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Fallback to static allowances below
        }

        $rawAllowances = [
            ['character_name' => 'Bart',            'allowance_sp' => 991787, 'rank' => 'Uzman',   'level' => 14],
            ['character_name' => 'Kerrik',          'allowance_sp' => 553770, 'rank' => 'Uzman',   'level' => 12],
            ['character_name' => 'Yanek',           'allowance_sp' => 398580, 'rank' => 'Uzman',   'level' => 11],
            ['character_name' => 'Taro',            'allowance_sp' => 323515, 'rank' => 'Uzman',   'level' => 12],
            ['character_name' => 'Raska',           'allowance_sp' => 215241, 'rank' => 'Uzman',   'level' => 11],
            ['character_name' => 'Belle',           'allowance_sp' => 157889, 'rank' => 'Uzman',   'level' => 10],
            ['character_name' => 'Ezekiel',         'allowance_sp' => 140398, 'rank' => 'Uzman',   'level' => 11],
            ['character_name' => 'Raphael',         'allowance_sp' => 112540, 'rank' => 'Kıdemli', 'level' => 9],
            ['character_name' => 'Dred\'nar',       'allowance_sp' => 104620, 'rank' => 'Uzman',   'level' => 11],
            ['character_name' => 'Arelion Vane',    'allowance_sp' => 97772,  'rank' => 'Uzman',   'level' => 11],
            ['character_name' => 'Baulbazahr',      'allowance_sp' => 95394,  'rank' => 'Uzman',   'level' => 12],
            ['character_name' => 'Duke',            'allowance_sp' => 89840,  'rank' => 'Uzman',   'level' => 12],
            ['character_name' => 'Azariah',         'allowance_sp' => 65003,  'rank' => 'Uzman',   'level' => 12],
            ['character_name' => 'Sango',           'allowance_sp' => 49614,  'rank' => 'Uzman',   'level' => 12],
            ['character_name' => 'Doran',           'allowance_sp' => 35742,  'rank' => 'Uzman',   'level' => 11],
            ['character_name' => 'Sangruiel Downb', 'allowance_sp' => 33020,  'rank' => 'Uzman',   'level' => 10],
            ['character_name' => 'Iivaria',         'allowance_sp' => 32224,  'rank' => 'Uzman',   'level' => 11],
            ['character_name' => 'Khal\'Azer\'oth', 'allowance_sp' => 27940,  'rank' => 'Uzman',   'level' => 11],
            ['character_name' => 'Desen',           'allowance_sp' => 23451,  'rank' => 'Kıdemli', 'level' => 9],
            ['character_name' => 'Fındık',          'allowance_sp' => 22850,  'rank' => 'Kıdemli', 'level' => 9],
            ['character_name' => 'Noodle',          'allowance_sp' => 22822,  'rank' => 'Kıdemli', 'level' => 10],
            ['character_name' => 'Vusta Ryhme',     'allowance_sp' => 20320,  'rank' => 'Kıdemli', 'level' => 9],
            ['character_name' => 'Ida',             'allowance_sp' => 12700,  'rank' => 'Kıdemli', 'level' => 9],
            ['character_name' => 'Morgana San',     'allowance_sp' => 12700,  'rank' => 'Kıdemli', 'level' => 9],
            ['character_name' => 'Thoma Quick',     'allowance_sp' => 8050,   'rank' => 'Uzman',   'level' => 12],
            ['character_name' => 'Cey-Cey',         'allowance_sp' => 5080,   'rank' => 'Kıdemli', 'level' => 8],
            ['character_name' => 'Mirang',          'allowance_sp' => 440,    'rank' => 'Uzman',   'level' => 11],
        ];

        $items = collect();
        $totalSp = 0;

        foreach ($rawAllowances as $a) {
            $totalSp += $a['allowance_sp'];

            if (!empty($filters['search'])) {
                $term = strtolower($filters['search']);
                if (!str_contains(strtolower($a['character_name']), $term)) {
                    continue;
                }
            }

            $badge = match ($a['rank']) {
                'Uzman' => 'bg-label-success',
                'Kıdemli' => 'bg-label-warning',
                default => 'bg-label-secondary',
            };

            $items->push((object) [
                'character_name' => $a['character_name'],
                'allowance_sp' => $a['allowance_sp'],
                'gp_equivalent' => round($a['allowance_sp'] / 10, 1),
                'rank' => $a['rank'],
                'level' => $a['level'],
                'effective_level' => $a['level'],
                'badge' => $badge,
            ]);
        }

        $count = $items->count();
        $averageSp = $count > 0 ? round($totalSp / $count, 1) : 0;
        $topEarner = $items->first();

        return [
            'items' => $items,
            'total_sp' => $totalSp,
            'total_gp' => round($totalSp / 10, 1),
            'average_sp' => $averageSp,
            'count' => $count,
            'top_earner' => $topEarner ? [
                'name' => $topEarner->character_name,
                'allowance_sp' => $topEarner->allowance_sp,
                'rank' => $topEarner->rank,
                'level' => $topEarner->effective_level,
            ] : [
                'name' => 'Bart',
                'allowance_sp' => 991787,
                'rank' => 'Uzman',
                'level' => 14,
            ],
        ];
    }

    /**
     * Valuable armory & vault items summary (Columns J-Q).
     * Reads from GuildVaultItem database table with fallback to static array.
     */
    public function getValuableInventorySummary(): array
    {
        try {
            if (class_exists(\App\Models\GuildVaultItem::class) && \App\Models\GuildVaultItem::count() > 0) {
                $vaultItems = \App\Models\GuildVaultItem::all();
                $items = collect();
                $totalWorth = 0;
                $totalGp = 0;
                $totalCount = 0;

                foreach ($vaultItems as $it) {
                    $resale = (float)($it->market_value_sp ?: ($it->total_price_sp * 0.55));
                    $gp = round($resale / 10, 1);
                    $qty = max(1, (int)$it->quantity);

                    $totalWorth += $resale;
                    $totalGp += $gp;
                    $totalCount += $qty;

                    $items->push((object) [
                        'name' => $it->item_name,
                        'quality' => $it->quality,
                        'type' => $it->item_type,
                        'quantity' => $qty,
                        'unit_price' => (float)$it->unit_price_sp,
                        'total_price' => (float)$it->total_price_sp,
                        'resale_price' => $resale,
                        'gp_value' => $gp,
                    ]);
                }

                return [
                    'items' => $items,
                    'total_worth_sp' => $totalWorth,
                    'total_gp' => round($totalGp, 1),
                    'total_items_count' => $totalCount,
                ];
            }
        } catch (\Throwable $e) {
            // Fallback to static items below
        }

        $rawItems = [
            ['name' => 'Amulet of Natural Armor',               'quality' => '+2',      'type' => 'Gear',          'quantity' => 1, 'unit_price' => 80000,  'total_price' => 80000,  'resale_price' => 44000,   'gp_value' => 4400.0],
            ['name' => 'Amulet of Natural Armor',               'quality' => '+1',      'type' => 'Gear',          'quantity' => 2, 'unit_price' => 20000,  'total_price' => 40000,  'resale_price' => 22000,   'gp_value' => 1100.0],
            ['name' => 'Amulet of the Spirits(Heavens)',        'quality' => null,      'type' => 'Gear',          'quantity' => 1, 'unit_price' => 80000,  'total_price' => 80000,  'resale_price' => 44000,   'gp_value' => 4400.0],
            ['name' => 'Armored Coat & Cloak of Resistance',    'quality' => '+1',      'type' => 'Armor',         'quantity' => 1, 'unit_price' => 32000,  'total_price' => 32000,  'resale_price' => 17600,   'gp_value' => 1760.0],
            ['name' => 'Bag of Holding',                        'quality' => null,      'type' => 'Gear',          'quantity' => 1, 'unit_price' => 25000,  'total_price' => 25000,  'resale_price' => 13750,   'gp_value' => 1375.0],
            ['name' => 'Bastard Sword',                         'quality' => '+1',      'type' => 'Weapon',        'quantity' => 1, 'unit_price' => 23350,  'total_price' => 23350,  'resale_price' => 12842.5, 'gp_value' => 1284.3],
            ['name' => 'Battle Axe',                            'quality' => '+3',      'type' => 'Weapon',        'quantity' => 1, 'unit_price' => 183100, 'total_price' => 183100, 'resale_price' => 100705,  'gp_value' => 10070.5],
            ['name' => 'Belt of Plague Rat',                    'quality' => null,      'type' => 'Gear',          'quantity' => 1, 'unit_price' => 52000,  'total_price' => 52000,  'resale_price' => 28600,   'gp_value' => 2860.0],
            ['name' => 'Belt of the Weasel',                    'quality' => null,      'type' => 'Gear',          'quantity' => 1, 'unit_price' => 100000, 'total_price' => 100000, 'resale_price' => 55000,   'gp_value' => 5500.0],
            ['name' => 'Boots of Gusto',                        'quality' => null,      'type' => 'Gear',          'quantity' => 1, 'unit_price' => 20000,  'total_price' => 20000,  'resale_price' => 11000,   'gp_value' => 1100.0],
            ['name' => 'Büyülü Pipo',                           'quality' => null,      'type' => 'Misc',          'quantity' => 1, 'unit_price' => 40000,  'total_price' => 40000,  'resale_price' => 22000,   'gp_value' => 2200.0],
            ['name' => 'Cap of Human Guise',                    'quality' => null,      'type' => 'Wondrous Item', 'quantity' => 1, 'unit_price' => 800,    'total_price' => 800,    'resale_price' => 440,     'gp_value' => 44.0],
            ['name' => 'Cloak of Elvenkind',                    'quality' => null,      'type' => 'Gear',          'quantity' => 1, 'unit_price' => 25000,  'total_price' => 25000,  'resale_price' => 13750,   'gp_value' => 1375.0],
            ['name' => 'Cloak of Resistance',                   'quality' => '+1',      'type' => 'Gear',          'quantity' => 2, 'unit_price' => 10000,  'total_price' => 20000,  'resale_price' => 11000,   'gp_value' => 550.0],
            ['name' => 'Composite Longbow Masterwork',          'quality' => '+1',      'type' => 'Weapon',        'quantity' => 1, 'unit_price' => 2000,   'total_price' => 2000,   'resale_price' => 1100,    'gp_value' => 110.0],
            ['name' => 'Dagger',                                'quality' => '+1, Holy','type' => 'Weapon',        'quantity' => 1, 'unit_price' => 183020, 'total_price' => 183020, 'resale_price' => 100661,  'gp_value' => 10066.1],
            ['name' => 'Dagger',                                'quality' => 'Cold Iron','type' => 'Weapon',       'quantity' => 1, 'unit_price' => 40,     'total_price' => 40,     'resale_price' => 22,      'gp_value' => 2.2],
            ['name' => 'Dragon Slaying Arrow',                  'quality' => null,      'type' => 'Ammunition',    'quantity' => 1, 'unit_price' => 22820,  'total_price' => 22820,  'resale_price' => 12551,   'gp_value' => 1255.1],
            ['name' => 'Dragonslayer\'s Shield',                'quality' => null,      'type' => 'Armor',         'quantity' => 1, 'unit_price' => 71700,  'total_price' => 71700,  'resale_price' => 39435,   'gp_value' => 3943.5],
            ['name' => 'Dusty Rose Prism (Ioun Stone)',         'quality' => 'Cracked', 'type' => 'Misc',          'quantity' => 1, 'unit_price' => 5000,   'total_price' => 5000,   'resale_price' => 2750,    'gp_value' => 275.0],
            ['name' => 'Dwarven Thrower',                       'quality' => null,      'type' => 'Weapon',        'quantity' => 1, 'unit_price' => 603120, 'total_price' => 603120, 'resale_price' => 331716,  'gp_value' => 33171.6],
            ['name' => 'Dweomer\'s Essence',                    'quality' => null,      'type' => 'Misc',          'quantity' => 3, 'unit_price' => 5000,   'total_price' => 15000,  'resale_price' => 8250,    'gp_value' => 275.0],
            ['name' => 'Earthbreaker',                          'quality' => '+3',      'type' => 'Weapon',        'quantity' => 1, 'unit_price' => 183400, 'total_price' => 183400, 'resale_price' => 100870,  'gp_value' => 10087.0],
            ['name' => 'Erinyes Bow',                           'quality' => '+1, Flaming','type' => 'Weapon',     'quantity' => 1, 'unit_price' => 89000,  'total_price' => 89000,  'resale_price' => 48950,   'gp_value' => 4895.0],
            ['name' => 'Falchion',                              'quality' => '+1',      'type' => 'Weapon',        'quantity' => 3, 'unit_price' => 23750,  'total_price' => 71250,  'resale_price' => 39187.5, 'gp_value' => 1306.3],
            ['name' => 'Feuchard',                              'quality' => '+1, Frost','type' => 'Weapon',       'quantity' => 1, 'unit_price' => 83140,  'total_price' => 83140,  'resale_price' => 45727,   'gp_value' => 4572.7],
            ['name' => 'Goggles of Night',                      'quality' => null,      'type' => 'Gear',          'quantity' => 1, 'unit_price' => 120000, 'total_price' => 120000, 'resale_price' => 66000,   'gp_value' => 6600.0],
            ['name' => 'Halberd',                               'quality' => '+1, Keen','type' => 'Weapon',        'quantity' => 1, 'unit_price' => 83100,  'total_price' => 83100,  'resale_price' => 45705,   'gp_value' => 4570.5],
            ['name' => 'Half Plate',                            'quality' => '+1',      'type' => 'Armor',         'quantity' => 2, 'unit_price' => 17500,  'total_price' => 35000,  'resale_price' => 19250,   'gp_value' => 962.5],
            ['name' => 'Hand Crossbow',                         'quality' => '+1',      'type' => 'Weapon',        'quantity' => 4, 'unit_price' => 24000,  'total_price' => 96000,  'resale_price' => 52800,   'gp_value' => 1320.0],
            ['name' => 'Headband of Alluring Charisma',         'quality' => '+2',      'type' => 'Gear',          'quantity' => 1, 'unit_price' => 40000,  'total_price' => 40000,  'resale_price' => 22000,   'gp_value' => 2200.0],
            ['name' => 'Heavy Mace',                            'quality' => '+1',      'type' => 'Weapon',        'quantity' => 1, 'unit_price' => 23120,  'total_price' => 23120,  'resale_price' => 12716,   'gp_value' => 1271.6],
        ];

        $items = collect();
        $totalWorth = 0;
        $totalGp = 0;
        $totalCount = 0;

        foreach ($rawItems as $it) {
            $totalWorth += $it['resale_price'];
            $totalGp += $it['gp_value'];
            $totalCount += $it['quantity'];

            $items->push((object) $it);
        }

        return [
            'items' => $items,
            'total_worth_sp' => $totalWorth,
            'total_gp' => round($totalGp, 1),
            'total_items_count' => $totalCount,
        ];
    }

    /**
     * Update a character's allowance (SP).
     */
    public function updateAllowance(string $characterName, int $allowanceSp): object
    {
        try {
            $adv = PlayerXp::where('character_name', $characterName)->first();
            if ($adv) {
                $adv->allowance_sp = max(0, $allowanceSp);
                $adv->save();
                return $adv;
            } else {
                $adv = PlayerXp::create([
                    'character_name' => $characterName,
                    'allowance_sp' => max(0, $allowanceSp),
                    'rank' => 'Uzman',
                    'level' => 12,
                    'total_xp' => 145000,
                    'status' => 'active',
                ]);
                return $adv;
            }
        } catch (\Throwable $e) {
            // If DB unavailable or column missing, return static object
        }

        return (object) [
            'character_name' => $characterName,
            'allowance_sp' => max(0, $allowanceSp),
        ];
    }

    /**
     * Update Bailiff council officer.
     */
    public function updateBailiff(int $id, array $data): object
    {
        try {
            $bailiff = GuildBailiff::find($id);
            if ($bailiff) {
                $bailiff->update($data);
                return $bailiff;
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return (object) array_merge(['id' => $id], $data);
    }

    /**
     * Update Guild level setting.
     */
    public function updateGuildLevel(int $level, int $nextLevelPoints): void
    {
        try {
            GuildSetting::set('guild_level', $level);
            GuildSetting::set('next_level_points', $nextLevelPoints);
        } catch (\Throwable $e) {
            // In static mode, ignore DB write failure
        }
    }

    /**
     * Ensure default data from screenshot is seeded.
     */
    public function ensureDefaultsSeeded(): void
    {
        try {
            if (GuildBailiff::count() === 0 || PlayerXp::count() === 0) {
                $this->resetToDefaults();
            }
        } catch (\Throwable $e) {
            // In static mode, ignore
        }
    }

    /**
     * Reset all data to the exact values in the Maceracı Info master data.
     */
    public function resetToDefaults(): void
    {
        try {
            // 1. Guild Settings
            GuildSetting::set('guild_level', 12);
            GuildSetting::set('next_level_points', 546);
            GuildSetting::set('current_level_points', 454);

            // 2. Bailiff Council (7 Members)
            GuildBailiff::truncate();
            $bailiffs = [
                ['character_name' => 'Thoma Quickfire', 'role' => 'Kahraman Kaynakları', 'assigned_date' => '14/7', 'order_index' => 1],
                ['character_name' => 'Temran',           'role' => 'Envanter',            'assigned_date' => '13/9', 'order_index' => 2],
                ['character_name' => 'Mirang',           'role' => 'Lonca Temsilcisi',     'assigned_date' => '27/8', 'order_index' => 3],
                ['character_name' => 'Zandar',           'role' => 'Questmaster',         'assigned_date' => '25/8', 'order_index' => 4],
                ['character_name' => 'Mart',             'role' => 'Arşivci',             'assigned_date' => '27/8', 'order_index' => 5],
                ['character_name' => 'Yanek',            'role' => 'Disiplin',            'assigned_date' => '3/7',  'order_index' => 6],
                ['character_name' => 'Bart',             'role' => 'Oathkeeper',          'assigned_date' => '13/1', 'order_index' => 7],
            ];

            foreach ($bailiffs as $b) {
                GuildBailiff::create($b);
            }

            // 3. Player XP baseline for allowances test
            if (PlayerXp::where('character_name', 'Bart')->doesntExist()) {
                PlayerXp::create([
                    'character_name' => 'Bart',
                    'allowance_sp' => 991787,
                    'rank' => 'Uzman',
                    'level' => 14,
                    'total_xp' => 400287,
                    'status' => 'active',
                ]);
            }
            if (PlayerXp::where('character_name', 'Kerrik')->doesntExist()) {
                PlayerXp::create([
                    'character_name' => 'Kerrik',
                    'allowance_sp' => 553770,
                    'rank' => 'Uzman',
                    'level' => 12,
                    'total_xp' => 145624,
                    'status' => 'active',
                ]);
            }

            // 4. Run AdventurerInfoSeeder to ensure updated vault items are seeded
            try {
                $seeder = new \Database\Seeders\AdventurerInfoSeeder();
                $seeder->run();
            } catch (\Throwable $se) {
                // Ignore if files missing
            }
        } catch (\Throwable $e) {
            // Ignore if DB fails
        }
    }

    public function resetToExcelDefaults(): void
    {
        $this->resetToDefaults();
    }
}

// Backward compatibility alias
if (!class_exists('App\ValiantShade\Services\MaceraciInfoService', false)) {
    class_alias(AdventurerInfoService::class, 'App\ValiantShade\Services\MaceraciInfoService');
}
