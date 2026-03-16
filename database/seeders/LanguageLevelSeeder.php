<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LanguageLevel;

class LanguageLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

        foreach ($levels as $level) {
            LanguageLevel::firstOrCreate([
                'name' => $level,
            ]);
        }
    }
}
