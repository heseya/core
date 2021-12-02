<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SeoMetadataResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'og_image' => MediaResource::make($this->media),
            'twitter_card' => $this->twitter_card,
        ];
    }
}
