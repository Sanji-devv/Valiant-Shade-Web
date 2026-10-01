<?php

namespace Database\Seeders;

use App\Models\LevelThreshold;
use Illuminate\Database\Seeder;

class LevelThresholdSeeder extends Seeder
{
    public function run(): void
    {
        $thresholds = [
            1  => 0.00,
            2  => 1300.00,
            3  => 3300.00,
            4  => 6000.00,
            5  => 10000.00,
            6  => 15000.00,
            7  => 23000.00,
            8  => 34000.00,
            9  => 50000.00,
            10 => 71000.00,
            11 => 105000.00,
            12 => 145000.00,
            13 => 210000.00,
            14 => 295000.00,
            15 => 425000.00,
            16 => 600000.00,
            17 => 850000.00,
            18 => 1200000.00,
            19 => 1700000.00,
            20 => 2400000.00,
        ];

        foreach ($thresholds as $lvl => $xp) {
            LevelThreshold::updateOrCreate(
                ['level' => $lvl],
                ['required_xp' => $xp]
            );
        }
    }
}