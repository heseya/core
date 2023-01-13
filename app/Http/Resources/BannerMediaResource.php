<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class BannerMediaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'url' => $this->resource->url,
            'title' => $this->resource->title,
            'subtitle' => $this->resource->subtitle,
            'media' => ResponsiveMediaResource::collection($this->resource->media),
        ];
    }
}
