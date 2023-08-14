<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SalesShortResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'percentage' => $this->resource->percentage,
            'amounts' => PriceResource::collection($this->resource->amounts),
        ];
    }
}
