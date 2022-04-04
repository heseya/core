<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OptionResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'price' => $this->resource->price,
            'disabled' => $this->resource->disabled,
            'available' => $this->resource->available,
            'items' => ItemPublicResource::collection($this->resource->items),
        ];
    }
}
