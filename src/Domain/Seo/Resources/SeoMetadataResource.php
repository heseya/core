<?php

declare(strict_types=1);

namespace Domain\Seo\Resources;

use App\Http\Resources\MediaResource;
use App\Http\Resources\Resource;
use App\Traits\GetAllTranslations;
use Illuminate\Http\Request;

final class SeoMetadataResource extends Resource
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
            'published' => $this->resource->published,
            ...$request->boolean('with_translations') ? $this->getAllTranslations() : [],
        ];
    }
}
