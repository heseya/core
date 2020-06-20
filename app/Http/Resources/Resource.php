<?php

namespace App\Http\Resources;

use Illuminate\Support\Arr;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return ['meta' => [
            'currency' => [
                'name' => 'Polski Złoty',
                'symbol' => 'PLN',
                'decimals' => 2,
            ],
            'language' => [
                'name' => 'Polski',
                'symbol' => 'PL-pl',
            ],
        ]];
    }

    public function base($request): array
    {
        return [];
    }

    public function view($request): array
    {
        return [];
    }

    public function index($request): array
    {
        return [];
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request, $index = false): array
    {
        if ($index) {
            return array_merge(
                $this->base($request),
                $this->index($request),
            );
        } else {
            return array_merge(
                $this->base($request),
                $this->view($request),
            );
        }
    }

    /**
     * Create new resource collection.
     *
     * @param  mixed  $resource
     * @return \App\Http\Resources\Collection
     */
    public static function collection($resource)
    {
        return new Collection($resource, static::class);
    }
}
