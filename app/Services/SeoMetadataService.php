<?php

namespace App\Services;

use App\Dtos\SeoKeywordsDto;
use App\Dtos\SeoMetadataDto;
use App\Enums\SeoModelType;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\SeoMetadata;
use App\Services\Contracts\SeoMetadataServiceContract;
use App\Services\Contracts\TranslationServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SeoMetadataService implements SeoMetadataServiceContract
{
    public function __construct(
        protected TranslationServiceContract $translationService,
    ) {
    }

    public function show(): SeoMetadata
    {
        return SeoMetadata::where('global', true)->firstOrFail();
    }

    public function createOrUpdate(SeoMetadataDto $dto): SeoMetadata
    {
        $seo = SeoMetadata::where('global', true)->first();

        if (!$seo) {
            /** @var SeoMetadata $seo */
            $seo = SeoMetadata::make($dto->toArray() + [
                'global' => true,
            ]);

            foreach ($dto->getTranslations() as $lang => $translations) {
                $translationArray = $translations->toArray() + [
                    'no_index' => $translations->getNoIndex() instanceof Missing
                        ? false
                        : $translations->getNoIndex(),
                ];

                foreach ($translationArray as $key => $translation) {
                    $seo->setTranslation($key, $lang, $translation);
                }
            }

            $this->translationService->checkPublished($seo, []);

            $seo->save();
        }

        if (!$seo->wasRecentlyCreated) {
            $seo->fill($dto->toArray());

            foreach ($dto->getTranslations() as $lang => $translations) {
                foreach ($translations->toArray() as $key => $translation) {
                    $seo->setTranslation($key, $lang, $translation);
                }
            }

            $this->translationService->checkPublished($seo, []);

            $seo->save();
        }

        Cache::put('seo.global', $seo);

        return $seo;
    }

    public function create(SeoMetadataDto $dto): SeoMetadata
    {
        /** @var SeoMetadata $seo */
        $seo = SeoMetadata::make($dto->toArray());

        $seo->setAttribute('no_index', '{}');

        foreach ($dto->getTranslations() as $lang => $translations) {
            $translationArray = $translations->toArray() + [
                'no_index' => $translations->getNoIndex() instanceof Missing
                        ? false
                        : $translations->getNoIndex(),
            ];

            foreach ($translationArray as $key => $translation) {
                $seo->setTranslation($key, $lang, $translation);
            }
        }

        $this->translationService->checkPublished($seo, []);

        $seo->save();

        return $seo;
    }

    public function update(SeoMetadataDto $dto, SeoMetadata $seoMetadata): SeoMetadata
    {
        $seoMetadata->fill($dto->toArray());

        foreach ($dto->getTranslations() as $lang => $translations) {
            foreach ($translations->toArray() as $key => $translation) {
                $seoMetadata->setTranslation($key, $lang, $translation);
            }
        }

        $this->translationService->checkPublished($seoMetadata, []);

        $seoMetadata->save();

        return $seoMetadata;
    }

    public function delete(SeoMetadata $seoMetadata): void
    {
        $seoMetadata->delete();
    }

    public function checkKeywords(SeoKeywordsDto $dto): Collection
    {
        $keywords = $dto->getKeywords();

        $excluded_id = $dto->getExcludedId();
        $excluded_model = $dto->getExcludedModel();

        $morph_closure = !$excluded_id instanceof Missing
            ? function (Builder $query, $type) use ($excluded_id, $excluded_model) {
                if ($type === SeoModelType::getValue(Str::upper(Str::snake($excluded_model)))) {
                    $query->where('model_id', '!=', $excluded_id);
                }
            }
        : null;

        $lang = App::getLocale();

        return SeoMetadata::whereHasMorph(
            'modelSeo',
            [
                Page::class,
                Product::class,
                ProductSet::class,
            ],
            $morph_closure
        )
            ->whereJsonLength("keywords->{$lang}", count($keywords))
            ->whereJsonContains("keywords->{$lang}", $keywords)
            ->get();
    }

    public function getGlobalSeo(): SeoMetadata | null
    {
        $seo = Cache::get('seo.global');

        if (!$seo) {
            $seo = SeoMetadata::where('global', true)->first();
            Cache::put('seo.global', $seo);
        }
        return $seo;
    }
}
