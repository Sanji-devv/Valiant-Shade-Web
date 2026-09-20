<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerXp extends Model
{
    use HasFactory;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get XP Thresholds dynamically from config/enums/xp_levels.php or XpLevel enum.
     */
    public static function getXpThresholds(): array
    {
        $path = config_path('enums/xp_levels.php');
        if (file_exists($path)) {
            $data = require $path;
            if (isset($data['thresholds'])) {
                return $data['thresholds'];
            }
        }

        return self::FAST_XP_THRESHOLDS;
    }

    /**
     * Get Level Failsafe mapping dynamically from enum.
     */
    public static function getXpFailsafes(): array
    {
        $path = config_path('enums/xp_levels.php');
        if (file_exists($path)) {
            $data = require $path;
            if (isset($data['failsafes'])) {
                return $data['failsafes'];
            }
        }

        return self::FAST_XP_THRESHOLDS;
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
     * - 'fallen': KIA or MIA
     * - 'inactive': rank is 'Inaktif' or status is 'inactive'
     * - 'candidate': rank is 'Aday'
     * - 'guild': active full guild member
     */
    public function getCategoryAttribute(): string
    {
        if (in_array($this->status, [self::STATUS_KIA, self::STATUS_MIA])) {
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

    /**
     * Base XP required for the current level.
     */
    public function getCurrentLevelBaseXp(): int
    {
        $thresholds = self::getXpThresholds();
        $lvl = self::calculateLevelFromXp($this->total_xp);
        return $thresholds[$lvl] ?? 0;
    }

    /**
     * XP required for the next level.
     */
    public function getNextLevelXp(): int
    {
        $thresholds = self::getXpThresholds();
        $lvl = self::calculateLevelFromXp($this->total_xp);
        if ($lvl >= 20) {
            return $thresholds[20] ?? 2400000;
        }
        $nextLevel = min(20, $lvl + 1);
        return $thresholds[$nextLevel] ?? ($thresholds[20] ?? 2400000);
    }

    /**
     * Calculate percentage progress towards next level.
     */
    public function getProgressPercentage(): float
    {
        $lvl = self::calculateLevelFromXp($this->total_xp);
        if ($lvl >= 20) {
            return 100.0;
        }

        $currentBase = $this->getCurrentLevelBaseXp();
        $nextBase = $this->getNextLevelXp();

        if ($nextBase <= $currentBase) {
            return 100.0;
        }

        $progress = (($this->total_xp - $currentBase) / ($nextBase - $currentBase)) * 100;
        return round(max(0, min(100, $progress)), 1);
    }

    /**
     * Formatted string like "129,141 / 145,000 XP".
     */
    public function getXpProgressLabel(): string
    {
        $lvl = self::calculateLevelFromXp($this->total_xp);
        if ($lvl >= 20) {
            return number_format($this->total_xp) . ' / ' . number_format($this->getNextLevelXp()) . ' XP (MAX)';
        }
        return number_format($this->total_xp) . ' / ' . number_format($this->getNextLevelXp()) . ' XP';
    }

    /**
     * Remaining XP needed to reach next level.
     */
    public function getRemainingXpToNextLevel(): int
    {
        $next = $this->getNextLevelXp();
        return max(0, $next - $this->total_xp);
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
        return $query->whereNotIn('status', [self::STATUS_KIA, self::STATUS_MIA]);
    }

    public function scopeGuildMembers($query)
    {
        return $query->whereNotIn('status', [self::STATUS_KIA, self::STATUS_MIA, 'inactive'])
                     ->whereNotIn('rank', ['Inaktif', 'Aday']);
    }

    public function scopeCandidates($query)
    {
        return $query->whereNotIn('status', [self::STATUS_KIA, self::STATUS_MIA])
                     ->where('rank', 'Aday');
    }

    public function scopeInactives($query)
    {
        return $query->whereNotIn('status', [self::STATUS_KIA, self::STATUS_MIA])
                     ->where(function ($q) {
                         $q->where('rank', 'Inaktif')->orWhere('status', 'inactive');
                     });
    }

    public function scopeFallen($query)
    {
        return $query->whereIn('status', [self::STATUS_KIA, self::STATUS_MIA]);
    }
}
