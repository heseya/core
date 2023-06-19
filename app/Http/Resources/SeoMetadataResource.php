<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SeoMetadataResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'keywords' => $this->resource->keywords,
            'og_image' => MediaResource::make($this->resource->media),
            'twitter_card' => $this->resource->twitter_card,
            'no_index' => $this->resource->no_index,
            'header_tags' => $this->resource->header_tags,
        ];
    }
}
