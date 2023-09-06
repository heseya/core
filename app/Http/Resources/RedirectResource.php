<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class RedirectResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'url' => $this->resource->url,
            'type' => $this->resource->type->value,
        ];
    }
}
