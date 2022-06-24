<?php

use App\Enums\EventType;

return [
    'permissions' => [
        EventType::ORDER_CREATED->value => ['orders.show_details', 'orders.show'],
        EventType::ORDER_UPDATED->value => ['orders.show_details', 'orders.show'],
        EventType::ORDER_UPDATED_STATUS->value => ['orders.show_details', 'orders.show'],
        EventType::PRODUCT_CREATED->value => ['products.show_details', 'products.show'],
        EventType::PRODUCT_UPDATED->value => ['products.show_details', 'products.show'],
        EventType::PRODUCT_DELETED->value => ['products.show_details', 'products.show'],
        EventType::ITEM_CREATED->value => ['items.show_details', 'items.show'],
        EventType::ITEM_UPDATED->value => ['items.show_details', 'items.show'],
        EventType::ITEM_UPDATED_QUANTITY->value => ['items.show_details', 'items.show'],
        EventType::ITEM_DELETED->value => ['items.show_details', 'items.show'],
        EventType::PAGE_CREATED->value => ['pages.show_details', 'pages.show'],
        EventType::PAGE_UPDATED->value => ['pages.show_details', 'pages.show'],
        EventType::PAGE_DELETED->value => ['pages.show_details', 'pages.show'],
        EventType::PRODUCT_SET_CREATED->value => ['product_sets.show_details', 'product_sets.show'],
        EventType::PRODUCT_SET_UPDATED->value => ['product_sets.show_details', 'product_sets.show'],
        EventType::PRODUCT_SET_DELETED->value => ['product_sets.show_details', 'product_sets.show'],
        EventType::USER_CREATED->value => ['users.show_details', 'users.show'],
        EventType::USER_UPDATED->value => ['users.show_details', 'users.show'],
        EventType::USER_DELETED->value => ['users.show_details', 'users.show'],
        EventType::SALE_CREATED->value => ['sales.show_details', 'sales.show'],
        EventType::SALE_UPDATED->value => ['sales.show_details', 'sales.show'],
        EventType::SALE_DELETED->value => ['sales.show_details', 'sales.show'],
        EventType::COUPON_CREATED->value => ['coupons.show_details', 'coupons.show'],
        EventType::COUPON_UPDATED->value => ['coupons.show_details', 'coupons.show'],
        EventType::COUPON_DELETED->value => ['coupons.show_details', 'coupons.show'],
        EventType::ADD_ORDER_DOCUMENT->value => ['orders.show_details', 'orders.show'],
        EventType::REMOVE_ORDER_DOCUMENT->value => ['orders.show_details', 'orders.show'],
        EventType::ORDER_UPDATED_PAID->value => ['orders.show_details', 'orders.show'],
    ],

    'permissions_hidden' => [
        EventType::PRODUCT_CREATED->value => ['products.show_hidden'],
        EventType::PRODUCT_UPDATED->value => ['products.show_hidden'],
        EventType::PRODUCT_DELETED->value => ['products.show_hidden'],
        EventType::PAGE_CREATED->value => ['pages.show_hidden'],
        EventType::PAGE_UPDATED->value => ['pages.show_hidden'],
        EventType::PAGE_DELETED->value => ['pages.show_hidden'],
        EventType::PRODUCT_SET_CREATED->value => ['product_sets.show_hidden'],
        EventType::PRODUCT_SET_UPDATED->value => ['product_sets.show_hidden'],
        EventType::PRODUCT_SET_DELETED->value => ['product_sets.show_hidden'],
    ],
];
