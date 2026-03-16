<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            'Nederlands',
            'Engels',
            'Duits',
            'Frans',
            'Spaans',
        ];

        foreach ($languages as $language) {
            Language::firstOrCreate([
                'name' => $language,
            ]);
        }
    }
}
