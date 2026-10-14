<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Tüm dünya haritalarını FK sırasına uygun şekilde doldurur.
 */
class WorldMapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ValiantShadeMapSeeder::class,
            IsenburgMapSeeder::class,
            IsonburgCityMapSeeder::class,
        ]);
    }
}
