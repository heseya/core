<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum DiscountTargetType: string
{
    use EnumTrait;

    case CHEAPEST_PRODUCT = 'cheapest-product';
    case ORDER_VALUE = 'order-value';
    case PRODUCTS = 'products';
    case SHIPPING_PRICE = 'shipping-price';

    public function getPriority(): int
    {
        return match ($this) {
            self::PRODUCTS => 0,
            self::CHEAPEST_PRODUCT => 1,
            self::ORDER_VALUE => 2,
            self::SHIPPING_PRICE => 3,
        };
    }
}
