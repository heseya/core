<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderSaleResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'value' => $this->resource->value,
            'type' => $this->resource->type,
            'target_type' => $this->resource->target_type,
        ];
    }
}
