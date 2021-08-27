<?php

namespace App\Http\Resources;

use Heseya\Resource\JsonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class Resource extends JsonResource
{
    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param Request $request
     */
    public function with($request): array
    {
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
            ],
        ];
    }
}
