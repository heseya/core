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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SeoMetadataService implements SeoMetadataServiceContract
{
    public function show(): SeoMetadata
    {
        return SeoMetadata::where('global', '=', true)->firstOrFail();
    }

    public function createOrUpdate(SeoMetadataDto $dto): SeoMetadata
    {
        $seo = SeoMetadata::firstOrCreate(
            ['global' => true],
            $dto->toArray()
        );

        if (!$seo->wasRecentlyCreated) {
            $seo->update($dto->toArray());
        }

        Cache::put('seo.global', $seo);

        return $seo;
    }

    public function create(SeoMetadataDto $dto): SeoMetadata
    {
        return SeoMetadata::create(
            $dto->toArray()
        );
    }

    public function update(SeoMetadataDto $dto, SeoMetadata $seoMetadata): SeoMetadata
    {
        $seoMetadata->update($dto->toArray());
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
            ? function (Builder $query, $type) use ($excluded_id, $excluded_model): void {
                if ($type === SeoModelType::getValue(Str::upper(Str::snake($excluded_model)))) {
                    $query->where('model_id', '!=', $excluded_id);
                }
            }
        : null;

        return SeoMetadata::whereHasMorph(
            'modelSeo',
            [
                Page::class,
                Product::class,
                ProductSet::class,
            ],
            $morph_closure
        )
            ->whereJsonLength('keywords', count($keywords))
            ->whereJsonContains('keywords', $keywords)
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
