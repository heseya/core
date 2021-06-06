<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Exceptions\StoreException;
use App\Models\Discount;
use App\Services\Contracts\DiscountServiceContract;

class DiscountService implements DiscountServiceContract
{
    public function calc(float $value, Discount $discount): float
    {
        if (isset($discount->pivot)) {
            $discount->type = $discount->pivot->type;
            $discount->discount = $discount->pivot->discount;
        }

        if ($discount->type->is(DiscountType::PERCENTAGE)) {
            return $value * $discount->discount / 100;
        }

        if ($discount->type->is(DiscountType::AMOUNT)) {
            return $discount->discount;
        }

        throw new StoreException('Discount type "' . $discount->type . '" is not supported');
    }
}
