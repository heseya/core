<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class DiscountTargetType extends Enum
{
    public const ORDER_VALUE = 'order-value';
    public const PRODUCTS = 'products';
    public const SHIPPING_PRICE = 'shipping-price';
    public const CHEAPEST_PRODUCT = 'cheapest-product';

    public static function getPriority($value): int
    {
        return match ($value) {
            self::PRODUCTS => 0,
            self::CHEAPEST_PRODUCT => 1,
            self::ORDER_VALUE => 2,
            self::SHIPPING_PRICE => 3,
        };
    }
}
