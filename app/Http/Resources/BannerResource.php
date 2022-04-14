<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class BannerResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'url' => $this->resource->url,
            'name' => $this->resource->name,
            'active' => $this->resource->active,
            'responsive_media' => $this->resource->responsiveMedia->map(
                fn ($item) => ResponsiveMediaResource::collection($item->media)
            ),
        ];
    }
}
