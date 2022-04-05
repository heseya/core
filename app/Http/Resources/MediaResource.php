<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'type' => Str::lower($this->resource->type->key),
            'url' => $this->resource->url,
            'slug' => $this->resource->slug,
            'alt' => $this->resource->alt,
        ], $this->metadataResource('media.show_metadata_private'));
    }
}
