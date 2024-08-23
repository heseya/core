<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Price;
use Domain\Price\Dtos\ProductCachedPriceDto;
use Illuminate\Http\Request;

/**
 * @property Price|ProductCachedPriceDto $resource
 */
class PriceCachedResource extends Resource
{
    public function __construct(Price|ProductCachedPriceDto $resource)
    {
        parent::__construct($resource);
    }

    public function base(Request $request): array
    {
        return [
            'net' => $this->resource->net?->getAmount() ?? 0,
            'gross' => $this->resource->gross?->getAmount() ?? 0,
            'currency' => $this->resource->currency,
            'sales_channel_id' => $this->resource->sales_channel_id,
        ];
    }
}
