<?php

namespace App\Traits;

use Domain\Language\LanguageService;
use Illuminate\Support\Facades\Config;

trait ModifyLangFallback
{
    protected function getCurrentLangFallbackSettings(): array
    {
        return [
            Config::get('translatable.fallback_locale'),
            Config::get('translatable.fallback_any'),
            Config::get('language.iso'),
        ];
    }

    protected function setLangFallbackSettings(?string $fallbackLocale, bool $fallbackAny, ?string $iso = null): void
    {
        Config::set('translatable.fallback_locale', $fallbackLocale);
        Config::set('translatable.fallback_any', $fallbackAny);
        Config::set('language.iso', $iso);
    }

    protected function setAnyLangFallback(): void
    {
        $languageService = app(LanguageService::class);
        $this->setLangFallbackSettings($languageService->defaultLanguage()->getKey(), true, 'any');
    }
}
