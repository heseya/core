<?php

use App\Page;
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
        factory(Page::class, 10)->create();

        factory(Page::class)->create([
            'name' => 'Regulamin',
            'slug' => 'regulamin',
            'public' => true,
            'content' => file_get_contents(__DIR__ . '/../pages/statute.md'),
        ]);

        factory(Page::class)->create([
            'name' => 'Polityka prywatności',
            'slug' => 'prywatnosc',
            'public' => true,
            'content' => file_get_contents(__DIR__ . '/../pages/privacy.md'),
        ]);

        factory(Page::class)->create([
            'name' => 'O nas',
            'slug' => 'o-nas',
            'public' => true,
            'content' => file_get_contents(__DIR__ . '/../pages/about.md'),
        ]);
    }
}
