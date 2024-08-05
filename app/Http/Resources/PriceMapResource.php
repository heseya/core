<?php

namespace App\Http\Resources;

use Domain\PriceMap\PriceMap;
use Illuminate\Http\Request;

/**
 * @property PriceMap $resource
 */
class PriceMapResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'currency' => $this->resource->currency->value,
            'is_net' => $this->resource->is_net,
        ];
    }
}
