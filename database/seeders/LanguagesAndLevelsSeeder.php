<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LanguagesAndLevelsSeeder extends Seeder
{
    /**
     * Seed only the languages and language_levels tables.
     * Run with: php artisan db:seed --class=LanguagesAndLevelsSeeder
     */
    public function run(): void
    {
        $this->call([
            LanguageLevelSeeder::class,
            LanguageSeeder::class,
        ]);
    }
}
