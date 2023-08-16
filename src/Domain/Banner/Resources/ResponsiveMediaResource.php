<?php

declare(strict_types=1);

namespace Domain\Banner\Resources;

use App\Http\Resources\MediaResource;
use App\Http\Resources\Resource;
use Illuminate\Http\Request;

final class ResponsiveMediaResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'min_screen_width' => $this->resource->pivot->min_screen_width,
            'media' => MediaResource::make($this->resource),
        ];
    }
}
