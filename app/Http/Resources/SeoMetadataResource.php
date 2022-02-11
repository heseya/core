<?php

namespace App\Http\Resources;

use App\Traits\GetAllTranslations;
use Illuminate\Http\Request;

class SeoMetadataResource extends Resource
{
    use GetAllTranslations;

    public function base(Request $request): array
    {
        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'og_image' => MediaResource::make($this->media),
            'twitter_card' => $this->twitter_card,
            'no_index' => $this->no_index,
        ];

        return array_merge(
            $data,
            $request->has('translations') ? $this->getAllTranslations() : []
        );
    }
}
