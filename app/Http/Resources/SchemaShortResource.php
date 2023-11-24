<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SchemaShortResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'price' => $this->resource->price,
            'hidden' => $this->resource->hidden,
            'required' => $this->resource->required,
            'available' => $this->resource->available,
            'default' => $this->resource->default,
        ];
    }
}
