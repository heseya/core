<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'type' => Str::lower($this->resource->type->key),
            'url' => $this->resource->url,
            'slug' => $this->resource->slug,
            'alt' => $this->resource->alt,
        ];
    }
}
