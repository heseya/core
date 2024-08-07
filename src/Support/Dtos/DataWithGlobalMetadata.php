<?php

declare(strict_types=1);

namespace Support\Dtos;

use App\Http\Resources\LanguageResource;
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

        $this->additional([
            'meta' => [
                'currency' => [
                    'name' => 'Polski Złoty',
                    'symbol' => 'PLN',
                    'decimals' => 2,
                ],
                'language' => LanguageResource::make(Config::get('language.model'))->toArray($request),
                'seo' => SeoMetadataResource::make($seoMetadataService->getGlobalSeo())->toArray($request),
            ],
        ]);

        return parent::toResponse($request);
    }
}
