<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminAndDevSeeder::class,
            TagSeeder::class,
            StudentAndVacancySeeder::class,
            LanguageLevelSeeder::class,
            LanguageSeeder::class,
        ]);
    }
}
