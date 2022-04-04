<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PackageTemplateResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'weight' => $this->resource->weight,
            'width' => $this->resource->width,
            'height' => $this->resource->height,
            'depth' => $this->resource->depth,
        ];
    }
}
