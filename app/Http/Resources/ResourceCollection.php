<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class ResourceCollection extends \Heseya\Resource\ResourceCollection
{
    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param Request $request
     */
    public function with($request): array
    {
        $meta = [
            'currency' => [
                'name' => 'Polski Złoty',
                'symbol' => 'PLN',
                'decimals' => 2,
            ],
            'language' => [
                'symbol' => App::currentLocale(),
            ],
        ];
        if ($request->user() !== null && $request->user()->hasPermissionTo('seo.show')) {
            $meta['seo'] = SeoMetadataResource::make(Cache::get('seo.global'));
        }
        return [
            'meta' => $meta,
        ];
    }
}
