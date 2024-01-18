<?php

namespace App\Traits;

use Domain\Language\LanguageService;
use Domain\SalesChannel\SalesChannelRepository;
use Illuminate\Support\Facades\App;

trait GetLocale
{
    public function getLocaleFromRequest(): string
    {
        if (!request()->header('Accept-Language')) {
            /** @var string|null $salesChannel */
            $salesChannel = request()->header('X-Sales-Channel');
            if ($salesChannel) {
                /** @var string $locale */
                $locale = app(SalesChannelRepository::class)->getOne($salesChannel)->defaultLanguage?->iso;
                return $locale;
            }
        }

        /** @var string $locale */
        $locale = app(LanguageService::class)->firstByIdOrDefault(App::getLocale())->iso;
        return $locale;
    }
}
