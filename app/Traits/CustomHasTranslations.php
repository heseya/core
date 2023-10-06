<?php

namespace App\Traits;

use App\Models\Discount;
use App\Models\Option;
use Domain\Language\LanguageService;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Spatie\Translatable\HasTranslations;

trait CustomHasTranslations
{
    use HasTranslations;

    public function hasPublishedColumn(): bool
    {
        return Schema::hasColumn($this->table, 'published');
    }

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
            return Arr::first($translatedLocales);
        }

        return $locale;
    }

    private function getAvailableTranslations(string $key): array
    {
        if ($this instanceof Discount) {
            $permission = $this->code !== null ? 'coupons.show_hidden' : 'sales.show_hidden';
        } else {
            $permission = $this::HIDDEN_PERMISSION;
        }
        if ($this instanceof SeoMetadata && !$this->global && $this->model_type) {
            $permission = Config::get('relation-aliases.' . $this->model_type)::HIDDEN_PERMISSION;
        }
        if (Auth::user() && Auth::user()->hasPermissionTo($permission)) {
            // published and no published
            /** @var Collection<int, string> $translations */
            $translations = $this->getTranslatedLocales($key);
        } else {
            // only published
            /** @var array<int, string> $translations */
            $translations = $this->published ?? [];
            if ($this instanceof Option) {
                /** @var array<int, string> $translations */
                $translations = $this->schema->published ?? [];
            }
            if ($this instanceof AttributeOption) {
                /** @var array<int, string> $translations */
                $translations = $this->attribute->published ?? [];
            }
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
