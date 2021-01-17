<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
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
            'brand' => BrandResource::make($this->brand),
            'category' => CategoryResource::make($this->category),
            'cover' => MediaResource::make($this->media()->first()),
        ];
    }

    public function view(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'original_id' => $this->original_id,
            'description_md' => $this->description_md,
            'description_html' => $this->description_html,
            'gallery' => MediaResource::collection($this->media),
            // 'schemas' => SchemaResource::collection($this->schemas),
        ];
    }
}
