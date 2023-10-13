<?php

namespace App\Traits;

use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelRepository;
use Illuminate\Support\Facades\Config;

trait GetLocale
{
    public function getLocaleFromRequest(): string
    {
        $locale = request()->header('Content-Language', Config::get('language.iso'));

        if (!$locale) {
            /** @var SalesChannel $salesChannel */
            $salesChannel = request()->header('X-Sales-Channel')
                ? app(SalesChannelRepository::class)->getOne(request()->header('X-Sales-Channel'))
                : app(SalesChannelRepository::class)->getDefault();
            $locale = $salesChannel->defaultLanguage->iso;
        }

        return $locale;
    }
}
