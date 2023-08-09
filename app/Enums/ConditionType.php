<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum ConditionType: string
{
    use EnumTrait;

    /**
     * If you're adding a new condition, remember to add its logic
     * in the DiscountService in method checkConditionForProduct().
     */
    case CART_LENGTH = 'cart-length';
    case COUPONS_COUNT = 'coupons-count';
    case DATE_BETWEEN = 'date-between';
    case MAX_USES = 'max-uses';
    case MAX_USES_PER_USER = 'max-uses-per-user';
    case ORDER_VALUE = 'order-value';
    case PRODUCT_IN = 'product-in';
    case PRODUCT_IN_SET = 'product-in-set';
    case TIME_BETWEEN = 'time-between';
    case USER_IN = 'user-in';
    case USER_IN_ROLE = 'user-in-role';
    case WEEKDAY_IN = 'weekday-in';
}
