<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
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
            'content_html' => file_get_contents(__DIR__ . '/../pages/statute.html'),
        ]);

        Page::factory()->create([
            'name' => 'Polityka prywatności',
            'slug' => 'prywatnosc',
            'public' => true,
            'content_html' => file_get_contents(__DIR__ . '/../pages/privacy.html'),
        ]);

        Page::factory()->create([
            'name' => 'O nas',
            'slug' => 'o-nas',
            'public' => true,
            'content_html' => file_get_contents(__DIR__ . '/../pages/about.html'),
        ]);
    }
}
