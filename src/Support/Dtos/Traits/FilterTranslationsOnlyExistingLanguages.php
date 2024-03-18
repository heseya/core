<?php

declare(strict_types=1);

namespace Support\Dtos\Traits;

use Domain\Language\Language;

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
     * @param array<string,mixed> $properties
     *
     * @return array<string,mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        $translations = $properties['translations'] ?? null;

        if (is_array($translations)) {
            $properties['translations'] = self::filterTranslationsOnlyExistingLanguages($translations);
        }

        return $properties;
    }
}
