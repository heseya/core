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
            'id' => $this->resource->getKey(),
            'value' => $this->resource->value->getAmount(),
        ];
    }
}
