<?php

namespace App\Http\Resources;

use App\Models\Media;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            // @phpstan-ignore-next-line
            'type' => Str::lower($this->resource->type->key),
            'source' => $this->resource->source->value,
            'url' => $this->resource->url,
            'slug' => $this->resource->slug,
            'alt' => $this->resource->alt,
        ], $this->metadataResource('media.show_metadata_private'));
    }
}
