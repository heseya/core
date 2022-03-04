<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class DiscountResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        if (isset($this->resource->pivot)) {
            $this->resource->type = $this->resource->pivot->type;
            $this->resource->discount = $this->resource->pivot->discount;
        }

        return [
            'id' => $this->getKey(),
            'code' => $this->code,
            'description' => $this->description,
            'discount' => $this->resource->discount,
            'type' => $this->resource->type,
            'uses' => $this->uses,
            'max_uses' => $this->max_uses,
            'available' => $this->available,
            'starts_at' => $this->starts_at,
            'expires_at' => $this->expires_at,
        ];
    }

    public function view(Request $request): array
    {
        return $this->metadataResource('coupons');
    }
}
