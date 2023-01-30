<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PriceResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'value' => $this->resource->value,
            'region_id' => $this->resource->region_id,
        ];
    }
}
