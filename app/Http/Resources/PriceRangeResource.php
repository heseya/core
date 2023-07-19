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
            'start' => [
                'gross' => $this->resource->start->getAmount(),
                'currency' => $this->resource->start->getCurrency()->getCurrencyCode(),
            ],
            'value' => [
                'gross' => $this->resource->value->getAmount(),
                'currency' => $this->resource->value->getCurrency()->getCurrencyCode(),
            ],
        ];
    }
}
