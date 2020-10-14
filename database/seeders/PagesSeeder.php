<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Page::factory()->count(10)->create();

        Page::factory()->create([
            'name' => 'Regulamin',
            'slug' => 'regulamin',
            'public' => true,
            'content_md' => file_get_contents(__DIR__ . '/../pages/statute.md'),
        ]);

        Page::factory()->create([
            'name' => 'Polityka prywatności',
            'slug' => 'prywatnosc',
            'public' => true,
            'content_md' => file_get_contents(__DIR__ . '/../pages/privacy.md'),
        ]);

        Page::factory()->create([
            'name' => 'O nas',
            'slug' => 'o-nas',
            'public' => true,
            'content_md' => file_get_contents(__DIR__ . '/../pages/about.md'),
        ]);
    }
}
