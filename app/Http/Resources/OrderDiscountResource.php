<?php

namespace App\Http\Resources;

use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Illuminate\Http\Request;

class OrderDiscountResource extends Resource
{
    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public function base(Request $request): array
    {
        $amount = $this->resource->pivot->amount ? Money::ofMinor(
            $this->resource->pivot->amount,
            $this->resource->pivot->currency,
        ) : null;

        $applied_discount = Money::ofMinor(
            $this->resource->pivot->applied_discount,
            $this->resource->pivot->currency,
        );

        return [
            'discount' => $this->resource->code !== null
                ? CouponResource::make($this)->baseOnly()
                : SaleResource::make($this)->baseOnly(),
            'name' => $this->resource->pivot->name,
            'code' => $this->resource->pivot->code,
            'percentage' => $this->resource->pivot->percentage,
            'amount' => $amount?->getAmount(),
            'target_type' => $this->resource->pivot->target_type,
            'applied_discount' => $applied_discount->getAmount(),
        ];
    }
}
