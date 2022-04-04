<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class DiscountResource extends Resource
{
    public function base(Request $request): array
    {
        if (isset($this->resource->pivot)) {
            $this->resource->type = $this->resource->pivot->type;
            $this->resource->discount = $this->resource->pivot->discount;
        }

        return [
            'id' => $this->resource->getKey(),
            'code' => $this->resource->code,
            'description' => $this->resource->description,
            'discount' => $this->resource->discount,
            'type' => $this->resource->type,
            'uses' => $this->resource->uses,
            'max_uses' => $this->resource->max_uses,
            'available' => $this->resource->available,
            'starts_at' => $this->resource->starts_at,
            'expires_at' => $this->resource->expires_at,
        ];
    }
}
