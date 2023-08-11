<?php

namespace Database\Seeders;

use Domain\Language\Language;
use Domain\ProductSet\ProductSet;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ProductSetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $language = Language::query()->where('default', false)->firstOrFail()->getKey();
        ProductSet::factory()->count(20)->create()->each(function ($set) use ($language): void {
            $rand = mt_rand(0, 4);
            $this->seo($set, $language);
            if ($rand === 0) {
                $sets = ProductSet::factory([
                    'parent_id' => $set->getKey(),
                ])->count(mt_rand(1, 2))->create();
                $sets->each(function ($newSet) use ($language) {
                    $this->seo($newSet, $language);
                    $this->translations($newSet, $language);
                });
            } elseif ($rand === 1) {
                $raw = ProductSet::factory()->raw();

                $newSet = ProductSet::factory([
                    'parent_id' => $set->getKey(),
                    'name' => $raw['name'],
                    'slug' => $set->slug . '-' . $raw['slug'],
                ])->create();
                $this->seo($newSet, $language);
                $this->translations($newSet, $language);
            }
            $this->translations($set, $language);
        });
    }

    private function seo(ProductSet $set, string $language): void
    {
        /** @var SeoMetadata $seo */
        $seo = SeoMetadata::factory()->create();
        $set->seo()->save($seo);
        $seoTranslation = SeoMetadata::factory()->definition();
        $seo->setLocale($language)->fill(Arr::only($seoTranslation, ['title', 'description', 'keywords', 'no_index']));
        $seo->fill(['published' => array_merge($seo->published, [$language])]);
        $seo->save();
    }

    private function translations(ProductSet $productSet, string $language): void
    {
        $translation = ProductSet::factory()->definition();
        $productSet->setLocale($language)->fill(Arr::only($translation, ['name', 'description_html']));
        $productSet->fill(['published' => array_merge($productSet->published, [$language])]);
        $productSet->save();
    }
}
