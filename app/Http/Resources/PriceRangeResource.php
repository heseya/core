<?php

namespace App\Http\Resources;

use App\Models\PriceRange;
use Illuminate\Http\Request;

/**
 * @property PriceRange $resource
 */
class PriceRangeResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'start' => $this->resource->start->getAmount(),
            'value' => $this->resource->value->getAmount(),
        ];
    }
}
