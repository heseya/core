<?php

namespace App\Http\Resources;

use App\Traits\GetAllTranslations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductResource extends Resource
{
    use GetAllTranslations;

    public function base(Request $request): array
    {
        $data = [
            'id' => $this->getKey(),
            'slug' => $this->slug,
            'name' => $this->name,
            'price' => $this->price,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'public' => $this->public,
            'visible' => $this->public,
            'available' => $this->available,
            'quantity_step' => $this->quantity_step,
            'cover' => MediaResource::make($this->media->first()),
            'tags' => TagResource::collection($this->tags),
            'published' => $this->published,
        ];

        return array_merge(
            $data,
            $request->has('translations') ? $this->getAllTranslations('products.show_hidden') : []
        );
    }

    public function view(Request $request): array
    {
        $sets = Auth::check() ? $this->sets : $this->sets()->public()->get();

        return [
            'user_id' => $this->user_id,
            'original_id' => $this->original_id,
            'description_html' => $this->description_html,
            'description_short' => $this->description_short,
            'meta_description' => str_replace("\n", ' ', trim(strip_tags($this->description_html))),
            'gallery' => MediaResource::collection($this->media),
            'schemas' => SchemaResource::collection($this->schemas),
            'sets' => ProductSetResource::collection($sets),
            'seo' => SeoMetadataResource::make($this->seo),
        ];
    }
}
