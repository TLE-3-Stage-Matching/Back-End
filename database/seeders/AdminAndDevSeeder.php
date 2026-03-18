<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminAndDevSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin account
        User::updateOrCreate(
            ['email' => 'admin@backend.com'],
            [
                'role' => UserRole::Admin,
                'first_name' => 'Admin',
                'middle_name' => null,
                'last_name' => 'User',
                'phone' => null,
                'password_hash' => 'Backend!Admin!2026',
            ],
        );

        // Dev accounts
        User::updateOrCreate(
            ['email' => 'dev@TeamA.com'],
            [
                'role' => UserRole::Dev,
                'first_name' => 'Dev',
                'middle_name' => null,
                'last_name' => 'TeamA',
                'phone' => null,
                'password_hash' => 'FrontendTeamAPassword!',
            ],
        );

        User::updateOrCreate(
            ['email' => 'dev@TeamB.com'],
            [
                'role' => UserRole::Dev,
                'first_name' => 'Dev',
                'middle_name' => null,
                'last_name' => 'TeamB',
                'phone' => null,
                'password_hash' => 'TeamBFrontendPassword2026!',
            ],
        );
    }
}

