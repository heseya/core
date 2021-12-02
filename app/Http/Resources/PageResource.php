<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PageResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'slug' => $this->slug,
            'name' => $this->name,
            'public' => $this->public,
            'order' => $this->order,
        ];
    }

    public function view(Request $request): array
    {
        return [
            'content_html' => $this->content_html,
            'meta_description' => str_replace("\n", ' ', trim(strip_tags($this->content_html))),
            'seo' => SeoMetadataResource::make($this->seo),
        ];
    }
}
