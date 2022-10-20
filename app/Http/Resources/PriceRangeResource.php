<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PriceRangeResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'start' => $this->resource->start,
            'prices' => PriceResource::collection($this->resource->prices),
        ];
    }
}
