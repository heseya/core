<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class PackageTemplateResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'weight' => $this->resource->weight,
            'width' => $this->resource->width,
            'height' => $this->resource->height,
            'depth' => $this->resource->depth,
        ], $this->metadataResource('packages.show_metadata_private'));
    }
}
