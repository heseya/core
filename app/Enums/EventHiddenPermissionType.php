<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EventHiddenPermissionType extends Enum
{
    public const PRODUCT_CREATED = 'products.show_hidden';
    public const PRODUCT_UPDATED = 'products.show_hidden';
    public const PRODUCT_DELETED = 'products.show_hidden';
    public const PAGE_CREATED = 'pages.show_hidden';
    public const PAGE_UPDATED = 'pages.show_hidden';
    public const PAGE_DELETED = 'pages.show_hidden';
    public const PRODUCT_SET_CREATED = 'product_sets.show_hidden';
    public const PRODUCT_SET_UPDATED = 'product_sets.show_hidden';
    public const PRODUCT_SET_DELETED = 'product_sets.show_hidden';
}
