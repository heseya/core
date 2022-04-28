<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TagResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'color' => $this->resource->color,
        ];
    }
}
