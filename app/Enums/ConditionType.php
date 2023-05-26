<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ConditionType extends Enum
{
    /**
     * If you're adding a new condition, remember to add its logic
     * in the DiscountService in method checkConditionForProduct().
     */
    public const ORDER_VALUE = 'order-value';
    public const USER_IN_ROLE = 'user-in-role';
    public const USER_IN = 'user-in';
    public const PRODUCT_IN_SET = 'product-in-set';
    public const PRODUCT_IN = 'product-in';
    public const DATE_BETWEEN = 'date-between';
    public const TIME_BETWEEN = 'time-between';
    public const MAX_USES = 'max-uses';
    public const MAX_USES_PER_USER = 'max-uses-per-user';
    public const WEEKDAY_IN = 'weekday-in';
    public const CART_LENGTH = 'cart-length';
    public const COUPONS_COUNT = 'coupons-count';
}
