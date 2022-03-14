<?php

use App\Enums\EventType;

return [
    EventType::class => [
        // Descriptions
        EventType::ORDER_CREATED => 'Event triggered when new orders are created',
        EventType::ORDER_UPDATED => 'Event triggered after order is updated',
        EventType::ORDER_UPDATED_STATUS => 'Event triggered after order status is updated',
        EventType::ORDER_REQUESTED_SHIPPING => 'Event triggered at creating shipping list',
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
        EventType::DISCOUNT_CREATED => 'Event triggered when new discounts are created',
        EventType::DISCOUNT_UPDATED => 'Event triggered after discount is updated',
        EventType::DISCOUNT_DELETED => 'Event triggered after discount is deleted',
    ]
];
