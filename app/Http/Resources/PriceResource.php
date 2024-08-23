<?php

declare(strict_types=1);

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
            // 'sales_channel_id' => $this->resource->sales_channel_id ?? null,
            // 'price_map_id' => $this->resource->price_map_id ?? null,
            'net' => $value->getAmount(),
            'gross' => $value->getAmount(),
            'currency' => $value->getCurrency()->getCurrencyCode(),
            'is_net' => $this->resource->is_net,
        ];
    }
}
