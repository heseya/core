<?php

use App\Enums\EventType;

return [
    EventType::class => [
        // Descriptions
        EventType::ORDER_CREATED->value => 'Event triggered when new orders are created',
        EventType::ORDER_UPDATED->value => 'Event triggered after order is updated',
        EventType::ORDER_UPDATED_STATUS->value => 'Event triggered after order status is updated',
        EventType::PRODUCT_CREATED->value => 'Event triggered when new products are created',
        EventType::PRODUCT_UPDATED->value => 'Event triggered after product is updated',
        EventType::PRODUCT_DELETED->value => 'Event triggered after product is deleted',
        EventType::ITEM_CREATED->value => 'Event triggered when new items are created',
        EventType::ITEM_UPDATED->value => 'Event triggered after item is updated',
        EventType::ITEM_UPDATED_QUANTITY->value => 'Event triggered after item quantity is updated',
        EventType::ITEM_DELETED->value => 'Event triggered after item is deleted',
        EventType::PAGE_CREATED->value => 'Event triggered when new pages are created',
        EventType::PAGE_UPDATED->value => 'Event triggered after page is updated',
        EventType::PAGE_DELETED->value => 'Event triggered after page is deleted',
        EventType::PRODUCT_SET_CREATED->value => 'Event triggered when new product sets are created',
        EventType::PRODUCT_SET_UPDATED->value => 'Event triggered after product set is updated',
        EventType::PRODUCT_SET_DELETED->value => 'Event triggered after product set is deleted',
        EventType::USER_CREATED->value => 'Event triggered when new users are created',
        EventType::USER_UPDATED->value => 'Event triggered after user is updated',
        EventType::USER_DELETED->value => 'Event triggered after user is deleted',
        EventType::SALE_CREATED->value => 'Event triggered when new sales are created',
        EventType::SALE_UPDATED->value => 'Event triggered after sale is updated',
        EventType::SALE_DELETED->value => 'Event triggered after sale is deleted',
        EventType::COUPON_CREATED->value => 'Event triggered when new coupons are created',
        EventType::COUPON_UPDATED->value => 'Event triggered after coupon is updated',
        EventType::COUPON_DELETED->value => 'Event triggered after coupon is deleted',
        EventType::ADD_ORDER_DOCUMENT->value => 'Event triggered after order document are created',
        EventType::REMOVE_ORDER_DOCUMENT->value => 'Event triggered after order document is deleted',
        EventType::ORDER_UPDATED_PAID->value => 'Event triggered after order paid status is updated',
        EventType::TFA_INIT->value => 'Event triggered when TFA as email is initialized',
        EventType::TFA_SECURITY_CODE->value => 'Event triggered when TFA security code is generated',
        EventType::TFA_RECOVERY_CODES_CHANGED->value => 'Event triggered when TFA recovery codes are generated',
        EventType::PASSWORD_RESET->value => 'Event triggered when reset password is requested',
        EventType::SUCCESSFUL_LOGIN_ATTEMPT->value => 'Event triggered when a successful login attempt is made',
        EventType::NEW_LOCALIZATION_LOGIN_ATTEMPT
            ->value => 'Event triggered after successful login attempt from new localization',
        EventType::FAILED_LOGIN_ATTEMPT->value => 'Event triggered when a failed login attempt is made',
        EventType::ORDER_UPDATED_SHIPPING_NUMBER->value => 'Event triggered after shipping number is updated',
    ],
];
