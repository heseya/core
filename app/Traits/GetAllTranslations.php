<?php

namespace App\Traits;

use Illuminate\Support\Facades\Gate;

trait GetAllTranslations
{
    /**
     * Returns all available translations for user.
     *
     * */
    protected function getAllTranslations(?string $permissions = null): array
    {
        $allTranslations = [];
        $languages = $this->resource->published;
        $dataTranslations = $this->resource->getTranslations(
            allowedLocales: $permissions !== null && Gate::allows($permissions) ? null : $languages,
        );

        foreach ($dataTranslations as $field => $translations) {
            foreach ($translations as $locale => $translation) {
                if (!array_key_exists($locale, $allTranslations)) {
                    $allTranslations[$locale] = [];
                }

                $allTranslations[$locale] += [$field => $translation];
            }
        }

        return ['translations' => count($allTranslations) > 0 ? $allTranslations : (object) $allTranslations];
    }
}
