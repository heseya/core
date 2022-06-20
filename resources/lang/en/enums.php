<?php

use App\Enums\EventType;

return [
    EventType::class => [
        // Descriptions
        EventType::ORDER_CREATED => 'Event triggered when new orders are created',
        EventType::ORDER_UPDATED => 'Event triggered after order is updated',
        EventType::ORDER_UPDATED_STATUS => 'Event triggered after order status is updated',
        EventType::PRODUCT_CREATED => 'Event triggered when new products are created',
        EventType::PRODUCT_UPDATED => 'Event triggered after product is updated',
        EventType::PRODUCT_DELETED => 'Event triggered after product is deleted',
        EventType::ITEM_CREATED => 'Event triggered when new items are created',
        EventType::ITEM_UPDATED => 'Event triggered after item is updated',
        EventType::ITEM_UPDATED_QUANTITY => 'Event triggered after item quantity is updated',
        EventType::ITEM_DELETED => 'Event triggered after item is deleted',
        EventType::PAGE_CREATED => 'Event triggered when new pages are created',
        EventType::PAGE_UPDATED => 'Event triggered after page is updated',
        EventType::PAGE_DELETED => 'Event triggered after page is deleted',
        EventType::PRODUCT_SET_CREATED => 'Event triggered when new product sets are created',
        EventType::PRODUCT_SET_UPDATED => 'Event triggered after product set is updated',
        EventType::PRODUCT_SET_DELETED => 'Event triggered after product set is deleted',
        EventType::USER_CREATED => 'Event triggered when new users are created',
        EventType::USER_UPDATED => 'Event triggered after user is updated',
        EventType::USER_DELETED => 'Event triggered after user is deleted',
        EventType::SALE_CREATED => 'Event triggered when new sales are created',
        EventType::SALE_UPDATED => 'Event triggered after sale is updated',
        EventType::SALE_DELETED => 'Event triggered after sale is deleted',
        EventType::COUPON_CREATED => 'Event triggered when new coupons are created',
        EventType::COUPON_UPDATED => 'Event triggered after coupon is updated',
        EventType::COUPON_DELETED => 'Event triggered after coupon is deleted',
        EventType::ADD_ORDER_DOCUMENT => 'Event triggered after order document are created',
        EventType::REMOVE_ORDER_DOCUMENT => 'Event triggered after order document is deleted',
        EventType::ORDER_UPDATED_PAID => 'Event triggered after order paid status is updated',
    ],
];
