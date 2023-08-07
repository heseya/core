<?php

declare(strict_types=1);

namespace Domain\Language;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\StoreException;
use Domain\Language\Dtos\LanguageCreateDto;
use Domain\Language\Dtos\LanguageUpdateDto;
use Domain\Language\Events\LanguageCreated;
use Domain\Language\Events\LanguageDeleted;
use Domain\Language\Events\LanguageUpdated;

final class LanguageService
{
    public function create(LanguageCreateDto $dto): Language
    {
        /** @var Language $language */
        $language = Language::query()->create($dto->toArray());

        if ($dto->default === true) {
            $this->defaultSet($language);
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

        LanguageDeleted::dispatch($language);

        $language->delete();
    }

    private function defaultSet(Language $language): void
    {
        Language::query()
            ->where('id', '!=', $language->getKey())
            ->update(['default' => false]);
    }
}
