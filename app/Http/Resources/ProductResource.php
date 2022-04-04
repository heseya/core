<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductResource extends Resource
{
    public function base(Request $request): array
    {
        return [
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
            'cover' => MediaResource::make($this->resource->media->first()),
            'tags' => TagResource::collection($this->resource->tags),
            'items' => ProductItemResource::collection($this->resource->items),
        ];
    }

    public function view(Request $request): array
    {
        $sets = Auth::check() ? $this->resource->sets : $this->resource->sets()->public()->get();

        return [
            'order' => $this->resource->order,
            'user_id' => $this->resource->user_id,
            'original_id' => $this->resource->original_id,
            'description_html' => $this->resource->description_html,
            'description_short' => $this->resource->description_short,
            'meta_description' => str_replace(
                "\n",
                ' ',
                trim(strip_tags($this->resource->description_html)),
            ),
            'gallery' => MediaResource::collection($this->resource->media),
            'schemas' => SchemaResource::collection($this->resource->schemas),
            'sets' => ProductSetResource::collection($sets),
            'seo' => SeoMetadataResource::make($this->resource->seo),
        ];
    }
}
