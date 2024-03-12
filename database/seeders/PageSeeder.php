<?php

namespace Database\Seeders;

use Domain\Language\Language;
use Domain\Page\Page;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

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

        $language = Language::query()->where('default', false)->firstOrFail()->getKey();
        $pages->each(function ($page) use ($language): void {
            $this->seo($page, $language);
            $this->translations($page, $language);
        });
    }

    private function seo(Page $page, string $language): void
    {
        /** @var SeoMetadata $seo */
        $seo = SeoMetadata::factory()->create();
        $page->seo()->save($seo);
        $seoTranslation = SeoMetadata::factory()->definition();
        $seo->setLocale($language)->fill(Arr::only($seoTranslation, ['title', 'description', 'keywords', 'no_index']));
        $seo->fill(['published' => array_merge($seo->published, [$language])]);
        $seo->save();
    }

    private function translations(Page $page, string $language): void
    {
        $translation = Page::factory()->definition();
        $page->setLocale($language)->fill(Arr::only($translation, ['name', 'content_html']));
        $page->fill(['published' => array_merge($page->published, [$language])]);
        $page->save();
    }
}
