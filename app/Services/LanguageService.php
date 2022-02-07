<?php

namespace App\Services;

use App\Dtos\LanguageDto;
use App\Events\LanguageCreated;
use App\Events\LanguageDeleted;
use App\Events\LanguageUpdated;
use App\Exceptions\StoreException;
use App\Models\Language;
use App\Services\Contracts\LanguageServiceContract;

class LanguageService implements LanguageServiceContract
{
    public function create(LanguageDto $dto): Language
    {
        $language = Language::create($dto->toArray());

        if ($dto->getDefault() === true) {
            $this->defaultSet($language);
        }

        LanguageCreated::dispatch($language);

        return $language;
    }

    public function defaultSet(Language $language): void
    {
        Language::where('id', '!=', $language->getKey())->update(['default' => false]);
    }

    public function update(Language $language, LanguageDto $dto): Language
    {
        $language->update($dto->toArray());

        if ($dto->getDefault() === true) {
            $this->defaultSet($language);
        }

        LanguageUpdated::dispatch($language);

        return $language;
    }

    public function delete(Language $language): void
    {
        if ($language->default === true) {
            throw new StoreException('You cannot delete the default language.');
        }

        if (Language::count() <= 1) {
            throw new StoreException('There must be at least one language.');
        }

        $language->delete();

        LanguageDeleted::dispatch($language);
    }
}
