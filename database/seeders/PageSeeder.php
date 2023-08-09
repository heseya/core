<?php

namespace Database\Seeders;

use Domain\Page\Page;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = Page::factory()->count(10)->create();

        $pages->add(Page::factory()->create([
            'name' => 'Regulamin',
            'slug' => 'regulamin',
            'public' => true,
            'content_html' => file_get_contents(__DIR__ . '/../pages/statute.html'),
        ]));

        $pages->add(Page::factory()->create([
            'name' => 'Polityka prywatności',
            'slug' => 'prywatnosc',
            'public' => true,
            'content_html' => file_get_contents(__DIR__ . '/../pages/privacy.html'),
        ]));

        $pages->add(Page::factory()->create([
            'name' => 'O nas',
            'slug' => 'o-nas',
            'public' => true,
            'content_html' => file_get_contents(__DIR__ . '/../pages/about.html'),
        ]));

        $pages->each(function ($page): void {
            $this->seo($page);
        });
    }

    private function seo(Page $page): void
    {
        $seo = SeoMetadata::factory()->create();
        $page->seo()->save($seo);
    }
}
