<?php

declare(strict_types=1);

namespace Support\Dtos;

use App\Http\Resources\LanguageResource;
use Domain\Currency\Currency;
use Domain\SalesChannel\SalesChannelService;
use Domain\Seo\Resources\SeoMetadataResource;
use Domain\Seo\SeoMetadataService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\Response;

abstract class DataWithGlobalMetadata extends Data
{
    public function toResponse($request): Response
    {
        /** @var SeoMetadataService $seoMetadataService */
        $seoMetadataService = App::make(SeoMetadataService::class);
        /** @var SalesChannelService $salesChannelService */
        $salesChannelService = App::make(SalesChannelService::class);

        $currency = ($salesChannelService->getCurrentRequestSalesChannel()?->priceMap?->currency ?? Currency::DEFAULT);

        $this->additional([
            'meta' => [
                'currency' => $currency->toCurrencyDto()->toArray(),
                'language' => LanguageResource::make(Config::get('language.model'))->toArray($request),
                'seo' => SeoMetadataResource::make($seoMetadataService->getGlobalSeo())->toArray($request),
            ],
        ]);

        return parent::toResponse($request);
    }
}
