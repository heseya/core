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
            'name' => $this->resource->name,
            'active' => $this->resource->active,
            'banner_media' => BannerMediaResource::collection($this->resource->bannerMedia),
        ], $this->metadataResource('banners.show_metadata_private'));
    }
}
