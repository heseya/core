<?php

namespace App\Http\Resources;

use App\Services\Contracts\SeoMetadataServiceContract;
use Heseya\Resource\JsonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class Resource extends JsonResource
{
    public static function collection($resource)
    {
        return tap(new ResourceCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

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
                'language' => [
                    'symbol' => App::currentLocale(),
                ],
                'seo' => SeoMetadataResource::make($seoMetadataService->getGlobalSeo()),
            ],
        ];
    }
}
