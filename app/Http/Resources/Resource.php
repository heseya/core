<?php

namespace App\Http\Resources;

use Heseya\Resource\JsonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

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
        $meta = [
            'currency' => [
                'name' => 'Polski Złoty',
                'symbol' => 'PLN',
                'decimals' => 2,
            ],
            'language' => LanguageResource::make(
                Config::get('language.model'),
            ),
        ];
        if ($request->user() !== null && $request->user()->hasPermissionTo('seo.show')) {
            $meta['seo'] = SeoMetadataResource::make(Cache::get('seo.global'));
        }
        return [
            'meta' => $meta,
        ];
    }
}
