<?php

namespace App\Dtos;

use App\Http\Requests\ProductIndexRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class ProductSearchDto extends Dto
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

    public static function instantiateFromRequest(ProductIndexRequest $request): self
    {
        return new self(
            search: $request->input('search'),
            sort: $request->input('sort'),
            ids: $request->input('ids', new Missing()),
            slug: $request->input('slug', new Missing()),
            name: $request->input('name', new Missing()),
            public: self::boolean('public', $request),
            available: self::boolean('available', $request),
            sets: self::array('sets', $request),
            tags: self::array('tags', $request),
            metadata: self::array('metadata', $request),
            metadata_private: self::array('metadata_private', $request),
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
