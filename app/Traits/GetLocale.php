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
                $salesChannel = app(SalesChannelRepository::class)->getOne($salesChannel);
                if ($salesChannel->defaultLanguage) {
                    return $salesChannel->defaultLanguage->iso;
                }
            }
        }

        return app(LanguageService::class)->firstByIdOrDefault(App::getLocale())->iso;
    }
}
