<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevelThreshold extends Model
{
    use HasFactory;

    protected $table = 'level_thresholds';
    protected $primaryKey = 'level';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'level',
        'required_xp',
    ];

    protected $casts = [
        'level' => 'integer',
        'required_xp' => 'float',
    ];

    /**
     * Calculate character level based on total XP.
     */
    public static function calculateLevelFromXp(float $xp): int
    {
        $thresholds = static::orderBy('level', 'asc')->pluck('required_xp', 'level')->toArray();
        if (empty($thresholds)) {
            // Fallback to fast progression config if database not yet seeded
            $thresholds = config('enums.xp_levels.thresholds', [
                1 => 0, 2 => 1300, 3 => 3300, 4 => 6000, 5 => 10000,
                6 => 15000, 7 => 23000, 8 => 34000, 9 => 50000, 10 => 71000,
                11 => 105000, 12 => 145000, 13 => 210000, 14 => 295000, 15 => 425000,
                16 => 600000, 17 => 850000, 18 => 1200000, 19 => 1700000, 20 => 2400000,
            ]);
        }

        $currentLevel = 1;
        foreach ($thresholds as $lvl => $reqXp) {
            if ($xp >= $reqXp && $lvl >= 1) {
                $currentLevel = (int) $lvl;
            }
        }

        return min(20, max(1, $currentLevel));
    }
}
