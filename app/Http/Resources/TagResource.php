<?php

namespace App\Http\Resources;

class TagResource extends Resource
{
    public function base($request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'color' => $this->resource->color,
        ];
    }
}
