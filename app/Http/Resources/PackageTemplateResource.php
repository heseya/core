<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class PackageTemplateResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'weight' => $this->weight,
            'width' => $this->width,
            'height' => $this->height,
            'depth' => $this->depth,
        ];
    }

    public function view(Request $request): array
    {
        return $this->metadataResource();
    }
}
