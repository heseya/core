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
            'net' => $this->resource->value->getAmount(),
            'gross' => $this->resource->value->getAmount(),
            'currency' => $this->resource->value->getCurrency()->getCurrencyCode(),
            'sales_channel_id' => $this->resource->sales_channel_id,
        ];
    }
}
