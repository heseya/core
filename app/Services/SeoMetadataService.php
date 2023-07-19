<?php

namespace App\Services;

use App\DTO\SeoMetadata\SeoKeywordsDto;
use App\DTO\SeoMetadata\SeoMetadataDto;
use App\Dtos\SeoMetadataDto as SeoMetadataDtoOld;
use App\Exceptions\PublishingException;
use App\Models\Model;
use App\Models\SeoMetadata;
use App\Services\Contracts\SeoMetadataServiceContract;
use App\Services\Contracts\TranslationServiceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelData\Optional;

class SeoMetadataService implements SeoMetadataServiceContract
{
    public function __construct(
        protected TranslationServiceContract $translationService,
    ) {}

    public function show(): SeoMetadata
    {
        return $this->getGlobalSeo();
    }

    /**
     * @throws PublishingException
     */
    public function createOrUpdate(SeoMetadataDto $dto): SeoMetadata
    {
        /** @var SeoMetadata|null $seo */
        $seo = SeoMetadata::query()->where('global', '=', true)->first();

        if ($seo === null) {
            $seo = new SeoMetadata($dto->toArray());
            $seo->global = true;

            foreach ($dto->translations as $lang => $translations) {
                $translationArray = $translations + [
                    'no_index' => $translations['no_index'] ?? false,
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

            foreach ($dto->translations as $lang => $translations) {
                foreach ($translations as $key => $translation) {
                    $seo->setTranslation($key, $lang, $translation);
                }
            }

            $this->translationService->checkPublished($seo, []);

            $seo->save();
        }

        Cache::put('seo.global', $seo);

        return $seo;
    }

    /**
     * Create or update seo for given model.
     *
     * @throws PublishingException
     */
    public function createOrUpdateFor(Model $model, SeoMetadataDto|SeoMetadataDtoOld $dto): void
    {
        $seo = new SeoMetadata($dto->toArray());
        $seo->global = false;

        $seo->setAttribute('no_index', '{}');

        foreach ($dto->translations as $lang => $translations) {
            $translationArray = $translations + [
                'no_index' => $translations['no_index'] ?? false,
            ];

            foreach ($translationArray as $key => $translation) {
                $seo->setTranslation($key, $lang, $translation);
            }
        }

        $this->translationService->checkPublished($seo, []);

        $seo->save();
    }

    /**
     * @throws PublishingException
     */
    public function update(SeoMetadataDto|SeoMetadataDtoOld $dto, SeoMetadata $seoMetadata): SeoMetadata
    {
        $seoMetadata->fill($dto->toArray());

        foreach ($dto->translations as $lang => $translations) {
            foreach ($translations as $key => $translation) {
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
        $lang = App::getLocale();
        $query = SeoMetadata::query();

        if (!($dto->excluded instanceof Optional)) {
            $query->whereHasMorph(
                'modelSeo',
                "App\\Models\\{$dto->excluded->model}",
                fn (Builder $query) => $query->where('model_id', '!=', $dto->excluded->id),
            );
        }

        return $query
            ->whereJsonLength("keywords->{$lang}", count($dto->keywords))
            ->whereJsonContains("keywords->{$lang}", $dto->keywords)
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
