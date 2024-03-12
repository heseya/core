<?php

declare(strict_types=1);

namespace Support\Dtos\Traits;

use Domain\Language\Language;
use Illuminate\Support\Collection;

trait FilterTranslationsOnlyExistingLanguages
{
    /**
     * @param array<string,array<string,string>> $translations
     *
     * @return array<string,array<string,string>>
     */
    protected static function filterTranslationsOnlyExistingLanguages(array $translations): array
    {
        $languages = Language::all();

        foreach ($translations as $language => $values) {
            if (!$languages->contains('id', '=', $language)) {
                unset($translations[$language]);
            }
        }

        return $translations;
    }

    /**
     * @param Collection<string,mixed> $properties
     *
     * @return Collection<string,mixed>
     */
    public static function prepareForPipeline(Collection $properties): Collection
    {
        $translations = $properties->get('translations');

        if (is_array($translations)) {
            $properties->put('translations', self::filterTranslationsOnlyExistingLanguages($translations));
        }

        return $properties;
    }
}
