<?php

declare(strict_types=1);

namespace Domain\Seo;

use App\Dtos\SeoMetadataDto as SeoMetadataDtoOld;
use App\Exceptions\PublishingException;
use App\Models\Contracts\SeoContract;
use App\Services\Contracts\TranslationServiceContract;
use Domain\Seo\Dtos\SeoKeywordsDto;
use Domain\Seo\Dtos\SeoMetadataCreateDto;
use Domain\Seo\Dtos\SeoMetadataUpdateDto;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelData\Optional;

final class SeoMetadataService
{
    public function __construct(
        protected TranslationServiceContract $translationService,
    ) {}

    public function show(): SeoMetadata
    {
        return $this->getGlobalSeo();
    }

    public function createOrUpdate(SeoMetadataCreateDto $dto): SeoMetadata
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

            $seo->save();
        }

        if (!$seo->wasRecentlyCreated) {
            $seo->fill($dto->toArray());

            foreach ($dto->translations as $lang => $translations) {
                foreach ($translations as $key => $translation) {
                    $seo->setTranslation($key, $lang, $translation);
                }
            }

            $seo->forgetAllTranslationsForNonexistingLanguages();

            $seo->save();
        }

        Cache::put('seo.global', $seo);

        return $seo;
    }

    /**
     * Create or update seo for given model.
     */
    public function createOrUpdateFor(SeoContract $model, SeoMetadataCreateDto|SeoMetadataDtoOld|SeoMetadataUpdateDto $dto): void
    {
        $seo = $model->seo ?? new SeoMetadata();
        $seo->fill($dto->toArray());
        $seo->global = false;

        if (!($dto->translations instanceof Optional)) {
            foreach ($dto->translations as $lang => $translations) {
                $translationArray = $translations + [
                    'no_index' => $translations['no_index'] ?? false,
                ];

                foreach ($translationArray as $key => $translation) {
                    $seo->setTranslation($key, $lang, $translation);
                }
            }
        }

        $model->seo()->save($seo);
    }

    /**
     * @throws PublishingException
     */
    public function update(SeoMetadataCreateDto|SeoMetadataDtoOld|SeoMetadataUpdateDto $dto, SeoMetadata $seoMetadata): SeoMetadata
    {
        $seoMetadata->fill($dto->toArray());

        if (!($dto->translations instanceof Optional)) {
            foreach ($dto->translations as $lang => $translations) {
                foreach ($translations as $key => $translation) {
                    $seoMetadata->setTranslation($key, $lang, $translation);
                }
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

    /**
     * @return Collection<int, SeoMetadata>
     */
    public function checkKeywords(SeoKeywordsDto $dto): Collection
    {
        $lang = App::getLocale();
        $query = SeoMetadata::query();

        if (!($dto->excluded instanceof Optional)) {
            $query->where('model_id', '!=', $dto->excluded->id);
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
