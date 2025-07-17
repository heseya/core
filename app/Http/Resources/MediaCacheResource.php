<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;

/**
 * @property Media $resource
 */
class MediaCacheResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'type' => $this->resource->type->value,
            'source' => $this->resource->source->value,
            'url' => $this->resource->url,
            'slug' => $this->resource->slug,
            'alt' => $this->resource->alt,
        ];
    }
}
