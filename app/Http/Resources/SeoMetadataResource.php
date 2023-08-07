<?php

namespace App\Http\Resources;

use App\Traits\GetAllTranslations;
use Illuminate\Http\Request;

class SeoMetadataResource extends Resource
{
    use GetAllTranslations;

    /**
     * @return array<string, mixed>
     */
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
            ...$request->boolean('with_translations') ? $this->getAllTranslations() : [],
        ];
    }
}
