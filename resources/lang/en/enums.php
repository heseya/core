<?php

use App\Enums\EventPermissionType;

return [
    EventPermissionType::class => [
        // Descriptions
        EventPermissionType::ORDER_CREATED[0] => 'Event triggered when new orders are created',
        EventPermissionType::ORDER_UPDATED[0] => 'Event triggered after order is updated',
        EventPermissionType::ORDER_DELETED[0] => 'Event triggered after order is deleted',
        EventPermissionType::PRODUCT_CREATED[0] => 'Event triggered when new products are created',
        EventPermissionType::PRODUCT_UPDATED[0] => 'Event triggered after product is updated',
        EventPermissionType::PRODUCT_DELETED[0] => 'Event triggered after product is deleted',
        EventPermissionType::ITEM_CREATED[0] => 'Event triggered when new items are created',
        EventPermissionType::ITEM_UPDATED[0] => 'Event triggered after item is updated',
        EventPermissionType::ITEM_UPDATED_QUANTITY[0] => 'Event triggered after item quantity is updated',
        EventPermissionType::ITEM_DELETED[0] => 'Event triggered after item is deleted',
        EventPermissionType::PAGE_CREATED[0] => 'Event triggered when new pages are created',
        EventPermissionType::PAGE_UPDATED[0] => 'Event triggered after page is updated',
        EventPermissionType::PAGE_DELETED[0] => 'Event triggered after page is deleted',
        EventPermissionType::PRODUCT_SET_CREATED[0] => 'Event triggered when new product sets are created',
        EventPermissionType::PRODUCT_SET_UPDATED[0] => 'Event triggered after product set is updated',
        EventPermissionType::PRODUCT_SET_DELETED[0] => 'Event triggered after product set is deleted',
        EventPermissionType::USER_CREATED[0] => 'Event triggered when new users are created',
        EventPermissionType::USER_UPDATED[0] => 'Event triggered after user is updated',
        EventPermissionType::USER_DELETED[0] => 'Event triggered after user is deleted',
        EventPermissionType::DISCOUNT_CREATED[0] => 'Event triggered when new discounts are created',
        EventPermissionType::DISCOUNT_UPDATED[0] => 'Event triggered after discount is updated',
        EventPermissionType::DISCOUNT_DELETED[0] => 'Event triggered after discount is deleted',
    ]
];
