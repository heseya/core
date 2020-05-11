<?php

namespace App\Http\Resources;

use App\Http\Resources\BrandResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\SchemaResource;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'price' => $this->price,
            'public' => $this->public,
            'visible' => $this->isPublic(),
            'digital' => $this->digital,
            'user_id' => $this->user_id,
            'original_id' => $this->original_id,
            'description_md' => $this->description_md,
            'description_html' => $this->description_html,
            'brand' => new BrandResource($this->brand),
            'category' => new CategoryResource($this->category),
            'cover' => new MediaResource($this->media()->first()),
            'gallery' => MediaResource::collection($this->media),
            'schemas' => SchemaResource::collection($this->schemas),
        ];
    }
}
