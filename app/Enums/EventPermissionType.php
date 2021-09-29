<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EventPermissionType extends Enum
{
    public const ORDER_CREATED = 'orders.show_details;orders.show';
    public const ORDER_UPDATED = 'orders.show_details;orders.show';
    public const ORDER_DELETED = 'orders.show_details;orders.show';
    public const PRODUCT_CREATED = 'products.show_details;products.show;products.show_hidden';
    public const PRODUCT_UPDATED = 'products.show_details;products.show;products.show_hidden';
    public const PRODUCT_DELETED = 'products.show_details;products.show;products.show_hidden';
    public const ITEM_CREATED = 'items.show_details;items.show';
    public const ITEM_UPDATED = 'items.show_details;items.show';
    public const ITEM_UPDATED_QUANTITY = 'items.show_details;items.show';
    public const ITEM_DELETED = 'items.show_details;items.show';
    public const PAGE_CREATED = 'pages.show_details;pages.show;pages.show_hidden';
    public const PAGE_UPDATED = 'pages.show_details;pages.show;pages.show_hidden';
    public const PAGE_DELETED = 'pages.show_details;pages.show;pages.show_hidden';
    public const PRODUCT_SET_CREATED = 'product_sets.show_details;product_sets.show;product_sets.show_hidden';
    public const PRODUCT_SET_UPDATED = 'product_sets.show_details;product_sets.show;product_sets.show_hidden';
    public const PRODUCT_SET_DELETED = 'product_sets.show_details;product_sets.show;product_sets.show_hidden';
    public const USER_CREATED = 'users.show_details;users.show';
    public const USER_UPDATED = 'users.show_details;users.show';
    public const USER_DELETED = 'users.show_details;users.show';
    public const DISCOUNT_CREATED = 'discounts.show_details;discounts.show';
    public const DISCOUNT_UPDATED = 'discounts.show_details;discounts.show';
    public const DISCOUNT_DELETED = 'discounts.show_details;discounts.show';
}
