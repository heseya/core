<?php

namespace App\Traits;

use Domain\Language\LanguageService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Spatie\Translatable\HasTranslations;

trait CustomHasTranslations
{
    use HasTranslations;

    protected function normalizeLocale(string $key, string $locale, bool $useFallbackLocale): string
    {
        $translatedLocales = $this->getAvailableTranslations($key);

        if (in_array($locale, $translatedLocales)) {
            return $locale;
        }

        if (!$useFallbackLocale) {
            return $locale;
        }

        $fallbackLocale = config('translatable.fallback_locale') ?? config('app.fallback_locale');
        if ($fallbackLocale !== null && in_array($fallbackLocale, $translatedLocales)) {
            return $fallbackLocale;
        }

        if (count($translatedLocales) > 0 && config('translatable.fallback_any')) {
            return $translatedLocales[0];
        }

        return $locale;
    }

    private function getAvailableTranslations(string $key): array
    {
        if (Auth::user() && Auth::user()->hasPermissionTo($this::HIDDEN_PERMISSION)) {
            // published and no published
            /** @var Collection<int, string> $translations */
            $translations = $this->getTranslatedLocales($key);
        } else {
            // only published
            /** @var array<int, string> $translations */
            $translations = $this->published ?? [];
        }

        // check if they can be hidden
        if (Auth::user() && !Auth::user()->hasPermissionTo('languages.show_hidden')) {
            /** @var LanguageService $languageService */
            $languageService = App::make(LanguageService::class);
            $hiddenLanguages = $languageService->hiddenLanguages();

            $translations = Collection::make($translations)->diff($hiddenLanguages)->toArray();
        }

        return $translations instanceof Collection ? $translations->toArray() : $translations;
    }
}
