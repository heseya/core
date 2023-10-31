<?php

namespace App\Http\Resources;

use App\Models\Discount;
use App\Models\OrderDiscount;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Illuminate\Http\Request;

/**
 * @property Discount $resource
 */
class OrderDiscountResource extends Resource
{
    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public function base(Request $request): array
    {
        $pivot = $this->resource->order_discount;
        assert($pivot instanceof OrderDiscount);

        $amount = $pivot->amount;
        $applied = $pivot->applied;

        return [
            'discount' => $this->resource->code !== null
                ? CouponResource::make($this)->baseOnly()
                : SaleResource::make($this)->baseOnly(),
            'name' => $pivot->name,
            'code' => $pivot->code,
            'percentage' => $pivot->percentage,
            'amount' => $amount?->getAmount(),
            'target_type' => $pivot->target_type,
            'applied_discount' => $applied?->getAmount(),
        ];
    }
}
