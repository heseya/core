<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum ConditionType: string
{
    use EnumUtilities;

    case ORDER_VALUE = 'order-value';
    case USER_IN_ROLE = 'user-in-role';
    case USER_IN = 'user-in';
    case PRODUCT_IN_SET = 'product-in-set';
    case PRODUCT_IN = 'product-in';
    case DATE_BETWEEN = 'date-between';
    case TIME_BETWEEN = 'time-between';
    case MAX_USES = 'max-uses';
    case MAX_USES_PER_USER = 'max-uses-per-user';
    case WEEKDAY_IN = 'weekday-in';
    case CART_LENGTH = 'cart-length';
    case COUPONS_COUNT = 'coupons-count';
}
