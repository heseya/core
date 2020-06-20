<?php

namespace App\Http\Resources;

use App\Http\Resources\BrandResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\SchemaResource;
use App\Http\Resources\CategoryResource;

class ProductResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function base($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'price' => $this->price,
            'public' => $this->public,
            'visible' => $this->isPublic(),
            'available' => $this->available,
            'digital' => $this->digital,
            'brand' => BrandResource::make($this->brand),
            'category' => CategoryResource::make($this->category),
            'cover' => MediaResource::make($this->media()->first()),
        ];
    }

    public function view($request): array
    {
        return [
            'user_id' => $this->user_id,
            'original_id' => $this->original_id,
            'description_md' => $this->description_md,
            'description_html' => $this->description_html,
            'gallery' => MediaResource::collection($this->media),
            'schemas' => SchemaResource::collection($this->schemas),
        ];
    }
}
