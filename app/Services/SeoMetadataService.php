<?php

namespace App\Services;

use App\Dtos\SeoKeywordsDto;
use App\Dtos\SeoMetadataDto;
use App\Enums\SeoModelType;
use App\Models\Model;
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
        return $this->getGlobalSeo();
    }

    public function createOrUpdate(SeoMetadataDto $dto): SeoMetadata
    {
        /** @var SeoMetadata $seo */
        $seo = SeoMetadata::query()->firstOrCreate(
            ['global' => true],
            $dto->toArray()
        );

        if (!$seo->wasRecentlyCreated) {
            $seo->update($dto->toArray());
        }

        Cache::put('seo.global', $seo);

        return $seo;
    }

    /**
     * Create or update seo for given model.
     */
    public function createOrUpdateFor(Model $model, SeoMetadataDto $dto): void
    {
        SeoMetadata::query()->updateOrCreate([
            'model_id' => $model->getKey(),
            'model_type' => $model::class,
        ], $dto->toArray());
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

        $morph_closure = $excluded_id instanceof Missing ? null :
            function (Builder $query, $type) use ($excluded_id, $excluded_model): void {
                if (!$excluded_model instanceof Missing
                    && $type === SeoModelType::getValue(Str::upper(Str::snake($excluded_model)))
                ) {
                    $query->where('model_id', '!=', $excluded_id);
                }
            };

        return SeoMetadata::query()->whereHasMorph('modelSeo', [
            Page::class,
            Product::class,
            ProductSet::class,
        ], $morph_closure)
            ->whereJsonLength('keywords', count($keywords))
            ->whereJsonContains('keywords', $keywords)
            ->get();
    }

    public function getGlobalSeo(): SeoMetadata
    {
        $seo = Cache::get('seo.global');

        if (!$seo) {
            $seo = SeoMetadata::query()->where('global', true)->first();

            if (!($seo instanceof SeoMetadata)) {
                $seo = new SeoMetadata();
            }

            Cache::put('seo.global', $seo);
        }

        return $seo;
    }
}
