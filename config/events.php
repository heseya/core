<?php

use App\Enums\EventType;

return [
    'permissions' => [
        EventType::ORDER_CREATED => ['orders.show_details', 'orders.show'],
        EventType::ORDER_UPDATED => ['orders.show_details', 'orders.show'],
        EventType::ORDER_DELETED => ['orders.show_details', 'orders.show'],
        EventType::PRODUCT_CREATED => ['products.show_details', 'products.show'],
        EventType::PRODUCT_UPDATED => ['products.show_details', 'products.show'],
        EventType::PRODUCT_DELETED => ['products.show_details', 'products.show'],
        EventType::ITEM_CREATED => ['items.show_details', 'items.show'],
        EventType::ITEM_UPDATED => ['items.show_details', 'items.show'],
        EventType::ITEM_UPDATED_QUANTITY => ['items.show_details', 'items.show'],
        EventType::ITEM_DELETED => ['items.show_details', 'items.show'],
        EventType::PAGE_CREATED => ['pages.show_details', 'pages.show'],
        EventType::PAGE_UPDATED => ['pages.show_details', 'pages.show'],
        EventType::PAGE_DELETED => ['pages.show_details', 'pages.show'],
        EventType::PRODUCT_SET_CREATED => ['product_sets.show_details', 'product_sets.show'],
        EventType::PRODUCT_SET_UPDATED => ['product_sets.show_details', 'product_sets.show'],
        EventType::PRODUCT_SET_DELETED => ['product_sets.show_details', 'product_sets.show'],
        EventType::USER_CREATED => ['users.show_details', 'users.show'],
        EventType::USER_UPDATED => ['users.show_details', 'users.show'],
        EventType::USER_DELETED => ['users.show_details', 'users.show'],
        EventType::DISCOUNT_CREATED => ['discounts.show_details', 'discounts.show'],
        EventType::DISCOUNT_UPDATED => ['discounts.show_details', 'discounts.show'],
        EventType::DISCOUNT_DELETED => ['discounts.show_details', 'discounts.show'],
    ],

    'permissions_hidden' => [
        EventType::PRODUCT_CREATED => ['products.show_hidden'],
        EventType::PRODUCT_UPDATED => ['products.show_hidden'],
        EventType::PRODUCT_DELETED => ['products.show_hidden'],
        EventType::PAGE_CREATED => ['pages.show_hidden'],
        EventType::PAGE_UPDATED => ['pages.show_hidden'],
        EventType::PAGE_DELETED => ['pages.show_hidden'],
        EventType::PRODUCT_SET_CREATED => ['product_sets.show_hidden'],
        EventType::PRODUCT_SET_UPDATED => ['product_sets.show_hidden'],
        EventType::PRODUCT_SET_DELETED => ['product_sets.show_hidden'],
    ],
];
