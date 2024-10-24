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
                'title_position' => is_infinite($this->resource->title_position) || is_nan($this->resource->title_position) ? null : $this->resource->title_position, // @phpstan-ignore-line,
                'title_relevancy' => is_infinite($this->resource->title_relevancy) || is_nan($this->resource->title_relevancy) ? null : $this->resource->title_relevancy, // @phpstan-ignore-line
                'title_natural_relevancy' => is_infinite($this->resource->title_natural_relevancy) || is_nan($this->resource->title_natural_relevancy) ? null : $this->resource->title_natural_relevancy, // @phpstan-ignore-line
                'title_words_relevancy' => is_infinite($this->resource->title_words_relevancy) || is_nan($this->resource->title_words_relevancy) ? null : $this->resource->title_words_relevancy, // @phpstan-ignore-line
                'content_natural_relevancy' => is_infinite($this->resource->content_natural_relevancy) || is_nan($this->resource->content_natural_relevancy) ? 0 : $this->resource->content_natural_relevancy, // @phpstan-ignore-line
                'content_relevancy' => is_infinite($this->resource->content_relevancy) || is_nan($this->resource->content_relevancy) ? 0 : $this->resource->content_relevancy, // @phpstan-ignore-line
                'slug_length' => is_infinite($this->resource->slug_length) || is_nan($this->resource->slug_length) ? 0 : $this->resource->slug_length, // @phpstan-ignore-line
            ],
        ];
    }
}
