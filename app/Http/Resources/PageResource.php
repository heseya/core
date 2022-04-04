<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PageResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'public' => $this->resource->public,
            'order' => $this->resource->order,
        ];
    }

    public function view(Request $request): array
    {
        return [
            'content_html' => $this->resource->content_html,
            'meta_description' => str_replace(
                "\n",
                ' ',
                trim(strip_tags($this->resource->content_html)),
            ),
            'seo' => SeoMetadataResource::make($this->resource->seo),
        ];
    }
}
