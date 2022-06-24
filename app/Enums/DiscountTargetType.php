<?php

namespace App\Enums;

use App\Traits\EnumUtilities;
use Exception;

enum DiscountTargetType: string
{
    use EnumUtilities;

    case ORDER_VALUE = 'order-value';
    case PRODUCTS = 'products';
    case SHIPPING_PRICE = 'shipping-price';
    case CHEAPEST_PRODUCT = 'cheapest-product';

    public static function getPriority(string $value): int
    {
        return match ($value) {
            self::PRODUCTS->value => 0,
            self::CHEAPEST_PRODUCT->value => 1,
            self::ORDER_VALUE->value => 2,
            self::SHIPPING_PRICE->value => 3,
            default => throw new Exception('Unknown discount target type'),
        };
    }
}
