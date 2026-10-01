<?php

namespace Database\Seeders;

use App\Models\Rank;
use Illuminate\Database\Seeder;

class RankSeeder extends Seeder
{
    public function run(): void
    {
        $ranks = [
            ['name' => 'Aday',          'base_floor_xp' => 0.00,     'hierarchy_order' => 1],
            ['name' => 'Clerk',         'base_floor_xp' => 3300.00,  'hierarchy_order' => 2],
            ['name' => 'Üye',           'base_floor_xp' => 1500.00,  'hierarchy_order' => 3],
            ['name' => 'Kıdemli',       'base_floor_xp' => 20067.50, 'hierarchy_order' => 4],
            ['name' => 'Uzman',         'base_floor_xp' => 98295.00, 'hierarchy_order' => 5],
            ['name' => 'Margrave',      'base_floor_xp' => 98295.00, 'hierarchy_order' => 6],
            ['name' => 'Margrave (U)',  'base_floor_xp' => 98295.00, 'hierarchy_order' => 7],
            ['name' => 'Temsilci',      'base_floor_xp' => 98295.00, 'hierarchy_order' => 8],
        ];

        foreach ($ranks as $rankData) {
            Rank::updateOrCreate(
                ['name' => $rankData['name']],
                $rankData
            );
        }
    }
}