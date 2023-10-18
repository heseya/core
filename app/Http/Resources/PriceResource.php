<?php

namespace App\Http\Resources;

use App\Models\Price;
use Brick\Money\Money;
use Domain\Price\Dtos\PriceDto;
use Illuminate\Http\Request;

/**
 * @property Money|Price|PriceDto $resource
 */
class PriceResource extends Resource
{
    public function base(Request $request): array
    {
        $value = $this->resource instanceof Money ? $this->resource : $this->resource->value;

        return [
            'net' => $value->getAmount(),
            'gross' => $value->getAmount(),
            'currency' => $value->getCurrency()->getCurrencyCode(),
        ];
    }
}
