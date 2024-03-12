<?php

declare(strict_types=1);

namespace Domain\Banner\Resources;

use App\Http\Resources\Resource;
use App\Traits\GetAllTranslations;
use Illuminate\Http\Request;

final class BannerMediaResource extends Resource
{
    use GetAllTranslations;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'url' => $this->resource->url,
            'title' => $this->resource->title,
            'subtitle' => $this->resource->subtitle,
            'media' => ResponsiveMediaResource::collection($this->resource->media),
            'published' => $this->resource->published,
            ...$request->boolean('with_translations') ? $this->getAllTranslations('banners.show_hidden') : [],
        ];
    }
}
