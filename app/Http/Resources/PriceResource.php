<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Brick\Money\Money;
use Domain\Price\Dtos\PriceDto;
use Domain\PriceMap\PriceMapProductPrice;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Illuminate\Http\Request;

/**
 * @property PriceDto|PriceMapSchemaOptionPrice|PriceMapProductPrice|Money $resource
 */
class PriceResource extends Resource
{
    public function __construct(Money|PriceDto|PriceMapProductPrice|PriceMapSchemaOptionPrice $resource)
    {
        parent::__construct($resource);
    }

    public function base(Request $request): array
    {
        return [
            'value' => $this->resource instanceof Money ? $this->resource->getAmount() : $this->resource->value->getAmount(),
            'currency' => $this->resource instanceof Money ? $this->resource->getCurrency()->getCurrencyCode() : $this->resource->currency,
            'is_net' => $this->resource instanceof Money ? true : $this->resource->is_net,
        ];
    }
}
