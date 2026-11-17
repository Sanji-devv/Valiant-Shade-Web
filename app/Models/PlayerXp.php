<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayerXp extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'player_xps';

    protected $fillable = [
        'user_id',
        'character_name',
        'player_name',
        'status',
        'rank',
        'level',
        'sessions_count',
        'rank_xp',
        'mission_xp',
        'report_xp',
        'bailiff_xp',
        'extra_xp',
        'bonus_xp',
        'total_xp',
        'inactive_threshold_xp',
        'allowance_sp',
        'reason',
        'notes',
    ];

    protected $casts = [
        'level' => 'integer',
        'sessions_count' => 'integer',
        'rank_xp' => 'integer',
        'mission_xp' => 'integer',
        'report_xp' => 'integer',
        'bailiff_xp' => 'integer',
        'extra_xp' => 'integer',
        'bonus_xp' => 'integer',
        'total_xp' => 'integer',
        'inactive_threshold_xp' => 'integer',
        'allowance_sp' => 'integer',
    ];

    /**
     * Pathfinder 1e Fast Advancement XP Thresholds.
     */
    public const FAST_XP_THRESHOLDS = [
        1  => 0,
        2  => 1300,
        3  => 3300,
        4  => 6000,
        5  => 10000,
        6  => 15000,
        7  => 23000,
        8  => 34000,
        9  => 50000,
        10 => 71000,
        11 => 105000,
        12 => 145000,
        13 => 210000,
        14 => 295000,
        15 => 425000,
        16 => 600000,
        17 => 850000,
        18 => 1200000,
        19 => 1700000,
        20 => 2400000,
    ];

    public const RANKS = [
        'Aday' => 'Aday',
        'Üye' => 'Üye',
        'Uzman' => 'Uzman',
        'Kıdemli' => 'Kıdemli',
        'Gezgin' => 'Gezgin',
        'Clerk' => 'Clerk',
        'Inaktif' => 'Inaktif',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_KIA = 'kia';
    public const STATUS_MIA = 'mia';
    public const STATUS_RETIRED = 'retired';

    protected static function booted(): void
    {
        static::saving(function (PlayerXp $p) {
            if (empty($p->rank)) {
                $lvl = $p->level ?: self::calculateLevelFromXp((int)$p->total_xp);
                $p->rank = self::calculateRankFromLevelAndSessions((int)$lvl, (int)$p->sessions_count);
            }
        });
    }

    /**
     * Calculate guild rank strictly based on level and games played (sessions count):
     * - Level 1: Aday
     * - Level 2-5: Üye
     * - Level 6-9: Kıdemli
     * - Level 10: If sessions_count >= 3 => Uzman; otherwise Kıdemli
     * - Level 11+: Uzman
     */
    public static function calculateRankFromLevelAndSessions(int $level, int $sessionsCount = 0): string
    {
        if ($level <= 1) {
            return 'Aday';
        }
        if ($level >= 2 && $level <= 5) {
            return 'Üye';
        }
        if ($level >= 6 && $level <= 9) {
            return 'Kıdemli';
        }
        if ($level === 10) {
            return $sessionsCount >= 3 ? 'Uzman' : 'Kıdemli';
        }
        return 'Uzman';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportXpEntries(): HasMany
    {
        return $this->hasMany(QuestReportXpEntry::class);
    }

    public static ?array $cachedThresholds = null;
    public static ?array $cachedFailsafes = null;

    /**
     * Get XP Thresholds dynamically from config/enums/xp_levels.php or XpLevel enum.
     */
    public static function getXpThresholds(): array
    {
        if (self::$cachedThresholds !== null) {
            return self::$cachedThresholds;
        }

        $path = config_path('enums/xp_levels.php');
        if (file_exists($path)) {
            $data = require $path;
            if (isset($data['thresholds'])) {
                return self::$cachedThresholds = $data['thresholds'];
            }
        }

        return self::$cachedThresholds = self::FAST_XP_THRESHOLDS;
    }

    /**
     * Get Level Failsafe mapping dynamically from enum.
     */
    public static function getXpFailsafes(): array
    {
        if (self::$cachedFailsafes !== null) {
            return self::$cachedFailsafes;
        }

        $path = config_path('enums/xp_levels.php');
        if (file_exists($path)) {
            $data = require $path;
            if (isset($data['failsafes'])) {
                return self::$cachedFailsafes = $data['failsafes'];
            }
        }

        return self::$cachedFailsafes = self::FAST_XP_THRESHOLDS;
    }

    /**
     * Calculate Level according to Pathfinder Fast Advancement Enum.
     */
    public static function calculateLevelFromXp(int $xp): int
    {
        $thresholds = self::getXpThresholds();
        $calculatedLevel = 1;
        foreach ($thresholds as $level => $threshold) {
            if ($level == 0) continue;
            if ($xp >= $threshold) {
                $calculatedLevel = (int) $level;
            } else {
                break;
            }
        }
        return $calculatedLevel;
    }

    /**
     * Get effective calculated level strictly based on total_xp and Pathfinder Fast thresholds.
     */
    public function getEffectiveLevelAttribute(): int
    {
        return self::calculateLevelFromXp($this->total_xp);
    }

    /**
     * Categorize adventurer into one of 4 dedicated lists:
     * - 'fallen': KIA, MIA, or retired
     * - 'inactive': rank is 'Inaktif' or status is 'inactive'
     * - 'candidate': rank is 'Aday'
     * - 'guild': active full guild member
     */
    public function getCategoryAttribute(): string
    {
        if (in_array($this->status, [self::STATUS_KIA, self::STATUS_MIA, self::STATUS_RETIRED], true)) {
            return 'fallen';
        }
        if ($this->rank === 'Inaktif' || $this->status === 'inactive') {
            return 'inactive';
        }
        if ($this->rank === 'Aday') {
            return 'candidate';
        }
        return 'guild';
    }

    protected ?array $computedProgress = null;

    /**
     * Compute all level progression stats in a single fast pass.
     */
    public function getProgressDetails(): array
    {
        if ($this->computedProgress !== null) {
            return $this->computedProgress;
        }

        $thresholds = self::getXpThresholds();
        $xp = (int) $this->total_xp;
        $lvl = self::calculateLevelFromXp($xp);

        $currentBase = $thresholds[$lvl] ?? 0;
        if ($lvl >= 20) {
            $nextBase = $thresholds[20] ?? 2400000;
            $percent = 100.0;
            $remaining = 0;
            $label = number_format($xp) . ' / ' . number_format($nextBase) . ' XP (MAX)';
        } else {
            $nextLvl = min(20, $lvl + 1);
            $nextBase = $thresholds[$nextLvl] ?? ($thresholds[20] ?? 2400000);
            $span = $nextBase - $currentBase;
            $percent = $span > 0 ? round(max(0, min(100, (($xp - $currentBase) / $span) * 100)), 1) : 100.0;
            $remaining = max(0, $nextBase - $xp);
            $label = number_format($xp) . ' / ' . number_format($nextBase) . ' XP';
        }

        return $this->computedProgress = [
            'level' => $lvl,
            'current_base' => $currentBase,
            'next_base' => $nextBase,
            'percent' => $percent,
            'remaining' => $remaining,
            'label' => $label,
        ];
    }

    /**
     * Base XP required for the current level.
     */
    public function getCurrentLevelBaseXp(): int
    {
        return $this->getProgressDetails()['current_base'];
    }

    /**
     * XP required for the next level.
     */
    public function getNextLevelXp(): int
    {
        return $this->getProgressDetails()['next_base'];
    }

    /**
     * Calculate percentage progress towards next level.
     */
    public function getProgressPercentage(): float
    {
        return $this->getProgressDetails()['percent'];
    }

    /**
     * Formatted string like "129,141 / 145,000 XP".
     */
    public function getXpProgressLabel(): string
    {
        return $this->getProgressDetails()['label'];
    }

    /**
     * Remaining XP needed to reach next level.
     */
    public function getRemainingXpToNextLevel(): int
    {
        return $this->getProgressDetails()['remaining'];
    }

    /**
     * Calculate computed total XP from sub-components.
     */
    public function calculateComputedTotalXp(): int
    {
        if (in_array($this->status, [self::STATUS_KIA, self::STATUS_MIA]) && $this->total_xp > 0 && ($this->rank_xp == 0 && $this->extra_xp == 0 && $this->bailiff_xp == 0)) {
            // For KIA/MIA, total_xp might be directly preserved from fallen records
            return $this->total_xp;
        }

        $sum = $this->rank_xp + $this->mission_xp + $this->report_xp + $this->bailiff_xp + $this->extra_xp + $this->bonus_xp;
        return $sum > 0 ? $sum : $this->total_xp;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_KIA, self::STATUS_MIA, self::STATUS_RETIRED]);
    }

    public function scopeGuildMembers($query)
    {
        return $query->whereNotIn('status', [self::STATUS_KIA, self::STATUS_MIA, self::STATUS_RETIRED, 'inactive'])
                     ->whereNotIn('rank', ['Inaktif', 'Aday']);
    }

    public function scopeCandidates($query)
    {
        return $query->whereNotIn('status', [self::STATUS_KIA, self::STATUS_MIA, self::STATUS_RETIRED])
                     ->where('rank', 'Aday');
    }

    public function scopeInactives($query)
    {
        return $query->whereNotIn('status', [self::STATUS_KIA, self::STATUS_MIA, self::STATUS_RETIRED])
                     ->where(function ($q) {
                         $q->where('rank', 'Inaktif')->orWhere('status', 'inactive');
                     });
    }

    public function scopeFallen($query)
    {
        return $query->whereIn('status', [self::STATUS_KIA, self::STATUS_MIA, self::STATUS_RETIRED]);
    }
}
