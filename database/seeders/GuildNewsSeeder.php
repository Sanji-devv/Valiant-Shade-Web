<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * GuildNewsSeeder is maintained for backward compatibility.
 * Delegates directly to the canonical NewsSeeder.
 */
class GuildNewsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(NewsSeeder::class);
    }
}
