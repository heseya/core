<?php

namespace App\Http\Resources;

use App\Models\Price;
use Domain\Price\Dtos\PriceDto;
use Domain\PriceMap\PriceMapProductPrice;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Illuminate\Http\Request;

/**
 * @property Price|PriceDto|PriceMapSchemaOptionPrice|PriceMapProductPrice $resource
 */
class PriceResource extends Resource
{
    public function __construct(Price|PriceDto|PriceMapProductPrice|PriceMapSchemaOptionPrice $resource)
    {
        parent::__construct($resource);
    }

    public function base(Request $request): array
    {
        $value = $this->resource->value;

        return [
            'price_map_id' => $this->resource->price_map_id,
            'net' => $value->getAmount(),
            'gross' => $value->getAmount(),
            'currency' => $value->getCurrency()->getCurrencyCode(),
        ];
    }
}
