<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'slug' => $this->slug,
            'name' => $this->name,
            'price' => $this->price,
            'public' => $this->public,
            'visible' => $this->isPublic(),
            'available' => $this->available,
            'quantity_step' => $this->quantity_step,
            'brand' => BrandResource::make($this->brand),
            'category' => CategoryResource::make($this->category),
            'cover' => MediaResource::make($this->media()->first()),
            'tags' => TagResource::collection($this->tags),
        ];
    }

    public function view(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'original_id' => $this->original_id,
            'description_md' => $this->description_md,
            'description_html' => $this->description_html,
            'meta_description' => str_replace("\n", ' ', trim(strip_tags($this->description_html))),
            'gallery' => MediaResource::collection($this->media),
            'schemas' => SchemaResource::collection($this->schemas),
        ];
    }
}
