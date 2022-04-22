<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'price' => $this->resource->price,
            'price_min' => $this->resource->price_min,
            'price_max' => $this->resource->price_max,
            'public' => $this->resource->public,
            'visible' => $this->resource->public,
            'available' => $this->resource->available,
            'quantity_step' => $this->resource->quantity_step,
            'google_product_category' => $this->resource->google_product_category,
            'cover' => MediaResource::make($this->resource->media->first()),
            'tags' => TagResource::collection($this->resource->tags),
            'items' => ProductItemResource::collection($this->resource->items),
            'min_price_discounted' => $this->resource->min_price_discounted ?? $this->resource->price_min,
            'max_price_discounted' => $this->resource->max_price_discounted ?? $this->resource->price_max,
        ], $this->metadataResource('products.show_metadata_private'));
    }

    public function view(Request $request): array
    {
        $sets = Auth::check() ? $this->resource->sets : $this->resource->sets()->public()->get();

        $sales = $this->resource->sales ? ['sales' => SaleResource::collection($this->resource->sales)] : [];

        return [
            'order' => $this->resource->order,
            'user_id' => $this->resource->user_id,
            'description_html' => $this->resource->description_html,
            'description_short' => $this->resource->description_short,
            'gallery' => MediaResource::collection($this->resource->media),
            'schemas' => SchemaResource::collection($this->resource->schemas),
            'sets' => ProductSetResource::collection($sets),
            'attributes' => ProductAttributeResource::collection($this->resource->attributes),
            'seo' => SeoMetadataResource::make($this->resource->seo),
        ] + $sales;
    }

    public function index(Request $request): array
    {
        return [
            'attributes' => ProductAttributeShortResource::collection($this->resource->attributes),
        ];
    }
}
