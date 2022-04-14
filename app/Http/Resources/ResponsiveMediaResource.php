<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ResponsiveMediaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'min_screen_width' => $this->resource->pivot->min_screen_width,
            'media' => MediaResource::make($this->resource),
        ];
    }
}
