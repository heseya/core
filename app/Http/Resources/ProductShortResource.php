<?php

namespace App\Http\Resources;

use App\Http\Resources\BrandResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductShortResource extends JsonResource
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
            'brand' => new BrandResource($this->brand),
            'category' => new CategoryResource($this->category),
            'cover' => new MediaResource($this->gallery()->first()),
        ];
    }
}
