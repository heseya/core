<?php

namespace App\Http\Resources;

use App\Services\Contracts\SeoMetadataServiceContract;
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
        /** @var SeoMetadataServiceContract $seoMetadataService */
        $seoMetadataService = App::make(SeoMetadataServiceContract::class);

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
