<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PackageTemplateResource extends Resource
{
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
}
