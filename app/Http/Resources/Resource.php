<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class Resource extends JsonResource
{
    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param Request $request
     * @return array
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

    public function base(Request $request): array
    {
        return [];
    }

    public function view(Request $request): array
    {
        return [];
    }

    public function index(Request $request): array
    {
        return [];
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @param bool $index
     * @return array
     */
    public function toArray($request, bool $index = false): array
    {
        if ($index) {
            return array_merge(
                $this->base($request),
                $this->index($request),
            );
        }

        return array_merge(
            $this->base($request),
            $this->view($request),
        );
    }

    /**
     * Create new resource collection.
     *
     * @param mixed $resource
     * @param bool $full
     * @return Collection
     */
    public static function collection($resource, bool $full = false): Collection
    {
        return new Collection($resource, static::class, $full);
    }
}
