<?php

namespace App\Http\Resources;

use App\Models\Price;
use Illuminate\Http\Request;

/**
 * @property Price $resource
 */
class PriceResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'gross' => $this->resource->value->getAmount(),
            'currency' => $this->resource->value->getCurrency()->getCurrencyCode(),
        ];
    }
}
