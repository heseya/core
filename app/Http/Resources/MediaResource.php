<?php

namespace App\Http\Resources;

use App\Models\Media;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

/**
 * @property Media $resource
 */
class MediaResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'type' => $this->resource->type->value,
            'source' => $this->resource->source->value,
            'url' => $this->resource->url,
            'slug' => $this->resource->slug,
            'alt' => $this->resource->alt,
        ], $this->metadataResource('media.show_metadata_private'));
    }
}
