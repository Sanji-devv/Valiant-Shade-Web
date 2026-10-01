<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@valiantshade.com'],
            [
                'username' => 'admin',
                'name' => 'Admin',
                'real_name' => 'Admin',
                'password' => '123456',
                'role' => 'ADMIN',
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );
    }
}
