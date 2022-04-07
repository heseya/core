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
            // @phpstan-ignore-next-line
            $this->resource->type = $this->resource->pivot->type;
            // @phpstan-ignore-next-line
            $this->resource->discount = $this->resource->pivot->discount;
        }

        return array_merge([
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
        ], $this->metadataResource('discounts.show_metadata_private'));
    }
}
