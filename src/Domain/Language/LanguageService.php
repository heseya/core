<?php

declare(strict_types=1);

namespace Domain\Language;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\StoreException;
use App\Models\Interfaces\Translatable;
use Domain\Language\Dtos\LanguageCreateDto;
use Domain\Language\Dtos\LanguageUpdateDto;
use Domain\Language\Events\LanguageCreated;
use Domain\Language\Events\LanguageDeleted;
use Domain\Language\Events\LanguageUpdated;
use Domain\Language\Jobs\RemoveTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

final class LanguageService
{
    public function create(LanguageCreateDto $dto): Language
    {
        /** @var Language $language */
        $language = Language::query()->create($dto->toArray());

        if ($dto->default === true) {
            $this->defaultSet($language);
        }

        if ($dto->hidden === true) {
            $this->updateHiddenLanguagesCache($language->getKey());
        }

        LanguageCreated::dispatch($language);

        return $language;
    }

    /**
     * @throws StoreException
     */
    public function update(Language $language, LanguageUpdateDto $dto): Language
    {
        if ($language->default && !$dto->default) {
            throw new StoreException(Exceptions::CLIENT_DUPLICATED_DEFAULT_LANGUAGE);
        }

        $language->update($dto->toArray());

        if ($dto->default === true) {
            $this->defaultSet($language);
        }

        if ($language->wasChanged('hidden')) {
            $this->updateHiddenLanguagesCache($language->getKey());

            if ($language->hidden) {
                $defaultLanguage = $this->defaultLanguage();
                $language->salesChannels()->update(['language_id' => $defaultLanguage->getKey()]);
            }
        }

        LanguageUpdated::dispatch($language);

        return $language;
    }

    /**
     * @throws StoreException
     */
    public function delete(Language $language): void
    {
        if ($language->default === true) {
            throw new StoreException(Exceptions::CLIENT_DELETE_DEFAULT_LANGUAGE);
        }

        if (Language::query()->count() <= 1) {
            throw new StoreException(Exceptions::CLIENT_NO_DEFAULT_LANGUAGE);
        }

        $this->updateHiddenLanguagesCache($language->getKey());

        LanguageDeleted::dispatch($language);
        RemoveTranslations::dispatch($language);

        $language->delete();
    }

    /**
     * @return Collection<int, string>
     */
    public function hiddenLanguages(): Collection
    {
        $hiddenLanguages = Cache::get('languages.hidden');
        if (!$hiddenLanguages) {
            $hiddenLanguages = Language::where('hidden', true)->pluck('id');
            Cache::put('languages.hidden', $hiddenLanguages);
        }

        return $hiddenLanguages;
    }

    public function removeAllLanguageTranslations(Language $language): void
    {
        $modelClasses = Config::get('translatable.models');
        /**
         * @var string $modelClass
         */
        foreach ($modelClasses as $modelClass) {
            $query = $modelClass::query();
            /** @var Translatable $model */
            $model = new $modelClass();
            $fields = $model->getTranslatableAttributes();

            $query->where(function (Builder $query) use ($fields): void {
                foreach ($fields as $field) {
                    $query->orWhere($field, '!=', null);
                }
            });

            $query->chunkById(100, function ($models) use ($language): void {
                foreach ($models as $model) {
                    $model->forgetAllTranslations($language->getKey());
                    if ($model->hasPublishedColumn()) {
                        $published = $model->published ?? [];
                        /** @var int|false $key */
                        $key = array_search($language->getKey(), $published, true);
                        if ($key !== false) {
                            array_splice($published, $key, 1);
                        }
                        $model->published = $published;
                    }
                    $model->save();
                }
            });
        }
    }

    public function defaultLanguage(): Language
    {
        $defaultLanguage = Cache::get('languages.default');
        if (!$defaultLanguage) {
            $defaultLanguage = Language::where('default', true)->first();
            Cache::put('languages.default', $defaultLanguage);
        }

        return $defaultLanguage;
    }

    public function firstByIsoOrDefault(string $iso): Language
    {
        /** @var Language|null $language */
        $language = Language::query()->where('iso', '=', $iso)->first();
        if ($language) {
            return $language;
        }

        return $this->defaultLanguage();
    }

    public function firstByIdOrDefault(string $id): Language
    {
        /** @var Language|null $language */
        $language = Language::query()->where('id', '=', $id)->first();
        if ($language) {
            return $language;
        }

        return $this->defaultLanguage();
    }

    private function updateHiddenLanguagesCache(string $uuid): void
    {
        $hiddenLanguages = Cache::get('languages.hidden');
        if ($hiddenLanguages) {
            $key = $hiddenLanguages->search($uuid);
            if ($key === false) {
                $hiddenLanguages->push($uuid);
            } else {
                $hiddenLanguages->forget($key);
            }
        } else {
            $hiddenLanguages = Collection::make([$uuid]);
        }

        Cache::put('languages.hidden', $hiddenLanguages);
    }

    private function defaultSet(Language $language): void
    {
        Language::query()
            ->where('id', '!=', $language->getKey())
            ->update(['default' => false]);

        Cache::put('languages.default', $language);
    }
}
