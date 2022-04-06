<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class PageResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'public' => $this->resource->public,
            'order' => $this->resource->order,
        ], $this->metadataResource('pages.show_metadata_private'));
    }

    public function view(Request $request): array
    {
        return [
            'content_html' => $this->resource->content_html,
            'seo' => SeoMetadataResource::make($this->resource->seo),
        ];
    }
}
