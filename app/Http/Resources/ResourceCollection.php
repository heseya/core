<?php

namespace App\Http\Resources;

use Domain\Seo\Resources\SeoMetadataResource;
use Domain\Seo\SeoMetadataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class ResourceCollection extends \Heseya\Resource\ResourceCollection
{
    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param Request $request
     */
    public function with($request): array
    {
        /** @var SeoMetadataService $seoMetadataService */
        $seoMetadataService = App::make(SeoMetadataService::class);

        return [
            'meta' => [
                'currency' => [
                    'name' => 'Polski Złoty',
                    'symbol' => 'PLN',
                    'decimals' => 2,
                ],
                'language' => LanguageResource::make(
                    Config::get('language.model'),
                ),
                'seo' => SeoMetadataResource::make($seoMetadataService->getGlobalSeo()),
            ],
        ];
    }
}
