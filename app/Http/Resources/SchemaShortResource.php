<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SchemaShortResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'type' => Str::lower($this->resource->type->name),
            'name' => $this->resource->name,
            'price' => $this->resource->price,
            'hidden' => $this->resource->hidden,
            'required' => $this->resource->required,
            'available' => $this->resource->available,
            'default' => $this->resource->default,
        ];
    }
}
