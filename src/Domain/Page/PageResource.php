<?php

declare(strict_types=1);

namespace Domain\Page;

use App\Http\Resources\Resource;
use App\Traits\GetAllTranslations;
use App\Traits\MetadataResource;
use Domain\Seo\Resources\SeoMetadataResource;
use Illuminate\Http\Request;

final class PageResource extends Resource
{
    use GetAllTranslations;
    use MetadataResource;

    /**
     * @return array<string, string>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'public' => $this->resource->public,
            'order' => $this->resource->order,
            'published' => $this->resource->published,
            ...$request->boolean('with_translations') ? $this->getAllTranslations('pages.show_hidden') : [],
            ...$this->metadataResource('pages.show_metadata_private'),
        ];
    }

    /**
     * @return array<string, string|SeoMetadataResource>
     */
    public function view(Request $request): array
    {
        return [
            'content_html' => $this->resource->content_html,
            'seo' => SeoMetadataResource::make($this->resource->seo),
        ];
    }
}
