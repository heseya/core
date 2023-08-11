<?php

declare(strict_types=1);

namespace Domain\Banner\Resources;

use App\Http\Resources\Resource;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

final class BannerResource extends Resource
{
    use MetadataResource;

    /**
     * @return array<string, mixed>
     */
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
