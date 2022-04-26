<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\ProductIndexRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProductSearchDto extends Dto implements InstantiateFromRequest
{
    private ?string $search;
    private ?string $sort;

    private string|Missing $ids;
    private string|Missing $slug;
    private string|Missing $name;

    private bool|Missing $public;
    private bool|Missing $available;

    private array|Missing $sets;
    private array|Missing $tags;
    private array|Missing $metadata;
    private array|Missing $metadata_private;

    private float|Missing $price_min;
    private float|Missing $price_max;

    public static function instantiateFromRequest(FormRequest|ProductIndexRequest $request): self
    {
        $sort = $request->input('sort');
        $sort = Str::contains($sort, 'price:asc')
            ? Str::replace('price:asc', 'price_min:asc', $sort) : $sort;
        $sort = Str::contains($sort, 'price:desc')
            ? Str::replace('price:desc', 'price_max:desc', $sort) : $sort;
        return new self(
            search: $request->input('search'),
            sort: $sort,
            ids: $request->input('ids', new Missing()),
            slug: $request->input('slug', new Missing()),
            name: $request->input('name', new Missing()),
            public: self::boolean('public', $request),
            available: self::boolean('available', $request),
            sets: self::array('sets', $request),
            tags: self::array('tags', $request),
            metadata: self::array('metadata', $request),
            metadata_private: self::array('metadata_private', $request),
            price_min: $request->input('price.min', new Missing()),
            price_max: $request->input('price.max', new Missing())
        );
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function getSort(): ?string
    {
        return $this->sort;
    }

    public function getPriceMin(): float|Missing
    {
        return $this->price_min;
    }

    public function getPriceMax(): float|Missing
    {
        return $this->price_max;
    }

    private static function boolean(string $key, ProductIndexRequest $request): bool|Missing
    {
        if (!$request->has($key)) {
            return new Missing();
        }

        return $request->boolean($key);
    }

    private static function array(string $key, ProductIndexRequest $request): array|Missing
    {
        if (!$request->has($key) || $request->input($key) === null) {
            return new Missing();
        }

        if (!is_array($request->input($key))) {
            return [$request->input($key)];
        }

        return $request->input($key);
    }
}
