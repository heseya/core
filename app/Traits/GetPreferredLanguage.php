<?php

namespace App\Traits;

use App\Models\Language;
use Illuminate\Support\Collection;

trait GetPreferredLanguage
{
    protected function getPreferredLanguage(?string $languageHeader, Collection $languages): Language
    {
        if ($languageHeader === null) {
            return $languages->firstWhere('default', true);
        }

        // Extract language codes from $languageHeader in priority order
        $headerLanguages = Collection::make();

        foreach (explode(',', $languageHeader) as $language) {
            if (!str_contains($language, ';')) {
                $priority = 1;
            } else {
                $priority = explode('=', $language)[1];
                $language = explode(';', $language)[0];
            }

            $group = $headerLanguages->get($priority, []);
            $group[] = $language;
            $headerLanguages->put($priority, $group);
        }

        $headerLanguages = $headerLanguages->sortKeysDesc()->flatten();

        // Get first matching language
        $languageCodes = $languages->mapWithKeys(
            // Map language iso to de-regionized iso
            fn (Language $language) => [
                explode('-', $language->iso)[0] => $language->iso,
            ],
        );

        foreach ($headerLanguages as $code) {
            // Check for exact matches
            if ($languageCodes->values()->contains($code)) {
                return $languages->firstWhere('iso', $code);
            }

            // Check for de-regionized matches
            if ($languageCodes->keys()->contains($code)) {
                return $languages->firstWhere('iso', $languageCodes[$code]);
            }
        }

        return $languages->firstWhere('default', true);
    }
}
