<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class kSalesShortResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'value' => round($this->resource->value, 2, PHP_ROUND_HALF_UP),
        ];
    }
}
