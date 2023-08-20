<?php

declare(strict_types=1);

namespace Database\Seeders;

use Domain\Language\Language;
use Domain\Metadata\Enums\MetadataType;
use Domain\ProductSet\ProductSet;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

final class ProductSetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = Language::all('iso', 'id');

        /** @var ProductSet $rootCategory */
        $rootCategory = ProductSet::factory()->create([
            'public' => true,
            'slug' => 'all',
            'name' => [
                $languages->where('iso', '=', 'pl')->value('id') => 'Kategorie',
                $languages->where('iso', '=', 'en')->value('id') => 'Categories',
            ],
            'description_html' => [
                $languages->where('iso', '=', 'pl')->value('id') => '<p>Głowna kategoria</p>',
                $languages->where('iso', '=', 'en')->value('id') => '<p>Main category</p>',
            ],
            'published' => $languages->pluck('id'),
        ]);
        $rootCategory->metadata()->create([
            'name' => 'nav',
            'value' => true,
            'value_type' => MetadataType::BOOLEAN,
            'public' => true,
        ]);
        $this->seo($rootCategory, $languages);

        for ($i = rand(20, 40); $i >= 0; $i--) {
            /** @var ProductSet $category */
            $category = ProductSet::factory()->create([
                'parent_id' => $rootCategory->getKey(),
            ]);
            $this->translations($category, $languages);
            $category->save();
            $category->metadata()->create([
                'name' => 'nav',
                'value' => true,
                'value_type' => MetadataType::BOOLEAN,
                'public' => true,
            ]);
            $category->metadata()->create([
                'name' => 'homepage',
                'value' => true,
                'value_type' => MetadataType::BOOLEAN,
                'public' => true,
            ]);
            $this->seo($category, $languages);
        }
    }

    /**
     * @param Collection<int, Language> $languages
     */
    private function seo(ProductSet $set, Collection $languages): void
    {
        /** @var SeoMetadata $seo */
        $seo = SeoMetadata::factory()->create();
        $set->seo()->save($seo);
        $attributes = Arr::only(
            SeoMetadata::factory()->definition(),
            ['title', 'description', 'keywords', 'no_index'],
        );
        foreach ($languages as $language) {
            $seo->setLocale($language->getKey())->fill($attributes);
        }
        $seo->published = $languages->pluck('id')->toArray();
        $seo->save();
    }

    /**
     * @param Collection<int, Language> $languages
     */
    private function translations(ProductSet $productSet, Collection $languages): void
    {
        $attributes = Arr::only(ProductSet::factory()->definition(), ['name', 'description_html']);
        foreach ($languages as $language) {
            $productSet->setLocale($language->getKey())->fill($attributes);
        }
        $productSet->published = $languages->pluck('id')->toArray();
    }
}
