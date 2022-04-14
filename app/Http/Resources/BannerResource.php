<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class BannerResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'url' => $this->resource->url,
            'name' => $this->resource->name,
            'active' => $this->resource->active,
            'responsive_media' => $this->resource->responsiveMedia->map(
                fn ($item) => ResponsiveMediaResource::collection($item->media)
            ),
        ], $this->metadataResource('banners.show_metadata_private'));
    }
}
