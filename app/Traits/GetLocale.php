<?php

namespace App\Traits;

use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelRepository;

trait GetLocale
{
    public function getLocaleFromRequest(): string
    {
        /** @var string|null $locale */
        $locale = request()->header('Content-Language');

        if (!$locale) {
            /** @var SalesChannel $salesChannel */
            $salesChannel = request()->header('X-Sales-Channel')
                ? app(SalesChannelRepository::class)->getOne(request()->header('X-Sales-Channel'))
                : app(SalesChannelRepository::class)->getDefault();
            /** @var string $locale */
            $locale = $salesChannel->defaultLanguage?->iso;
        }

        return $locale;
    }
}
