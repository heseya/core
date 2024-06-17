<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @property Product $resource
 */
class ProductResource extends ProductWithoutSalesResource
{
    public function base(Request $request): array
    {
        return array_merge(parent::base($request), [
            'published' => $this->resource->published,
        ]);
    }

    public function view(Request $request): array
    {
        return array_merge(parent::view($request), [
            'sales' => ProductSaleResource::collection($this->resource->sales),
        ]);
    }

    public function index(Request $request): array
    {
        return [
            'attributes' => ($request->filled('attribute_slug') || $this->resource->relationLoaded('productAttributes'))
                ? ProductAttributeShortResource::collection(
                    $this->resource->relationLoaded('productAttributes')
                        ? $this->resource->productAttributes
                        : $this->resource->productAttributes()->slug(explode(';', $request->input('attribute_slug')))->get(),
                )
                : [],
            'relevancy' => [
                'title_position' => $this->resource->title_position, // @phpstan-ignore-line,
                'title_relevancy' => $this->resource->title_relevancy, // @phpstan-ignore-line
                'title_natural_relevancy' => $this->resource->title_natural_relevancy, // @phpstan-ignore-line
                'title_words_relevancy' => $this->resource->title_words_relevancy, // @phpstan-ignore-line
                'content_natural_relevancy' => $this->resource->content_natural_relevancy, // @phpstan-ignore-line
                'content_relevancy' => $this->resource->content_relevancy, // @phpstan-ignore-line
                'slug_length' => $this->resource->slug_length, // @phpstan-ignore-line
            ],
        ];
    }
}
