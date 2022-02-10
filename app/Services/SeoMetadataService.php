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
use Heseya\Dto\Missing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SeoMetadataService implements SeoMetadataServiceContract
{
    public function show(): SeoMetadata
    {
        return SeoMetadata::where('global', true)->firstOrFail();
    }

    public function createOrUpdate(SeoMetadataDto $dto): SeoMetadata
    {
        $seo = SeoMetadata::where('global', '=', true)->first();

        if (!$seo) {
            $seo = SeoMetadata::make($dto->toArray() + [
                'global' => true,
                'no_index' => $dto->getNoIndex() instanceof Missing ? true : $dto->getNoIndex(),
            ]);

            $seo->setTranslation('keywords', App::getLocale(), $dto->getKeywords())
                ->save();
        }

        if (!$seo->wasRecentlyCreated) {
            $seo->fill($dto->toArray())
                ->setTranslation('keywords', App::getLocale(), $dto->getKeywords())
                ->save();
        }

        Cache::put('seo.global', $seo);

        return $seo;
    }

    public function create(SeoMetadataDto $dto): SeoMetadata
    {
        /** @var SeoMetadata $seo */
        $seo = SeoMetadata::make($dto->toArray() + [
            'no_index' => $dto->getNoIndex() instanceof Missing ? false : $dto->getNoIndex(),
        ]);

        $seo->setTranslation('keywords', App::getLocale(), $dto->getKeywords())
            ->save();

        return $seo;
    }

    public function update(SeoMetadataDto $dto, SeoMetadata $seoMetadata): SeoMetadata
    {
        $seoMetadata
            ->fill($dto->toArray())
            ->setTranslation('keywords', App::getLocale(), $dto->getKeywords())
            ->save();

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
