<?php

namespace App\Traits;

trait GetAllTranslations
{
    /**
     * Returns all available translations for user
     *
     * @return array
     * */
    protected function getAllTranslations(): array
    {
        $allTranslations = [];
        $languages = $this->published;

        foreach ($this->getTranslations(allowedLocales: $languages) as $field => $translations) {
            foreach ($translations as $locale => $translation) {
                if (!array_key_exists($locale, $allTranslations)) {
                    $allTranslations[$locale] = [];
                }

                $allTranslations[$locale] += [$field => $translation];
            }
        }

        return ['translations' => $allTranslations];
    }
}
