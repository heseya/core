<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;
use Illuminate\Support\Str;

final class EventPermissionType extends Enum implements LocalizedEnum
{
    public const ORDER_CREATED = ['order_created', 'orders.show_details', 'orders.show'];
    public const ORDER_UPDATED = ['order_updated', 'orders.show_details', 'orders.show'];
    public const ORDER_DELETED = ['order_deleted', 'orders.show_details', 'orders.show'];
    public const PRODUCT_CREATED = ['product_created', 'products.show_details', 'products.show'];
    public const PRODUCT_UPDATED = ['product_updated', 'products.show_details', 'products.show'];
    public const PRODUCT_DELETED = ['product_deleted', 'products.show_details', 'products.show'];
    public const ITEM_CREATED = ['item_created', 'items.show_details', 'items.show'];
    public const ITEM_UPDATED = ['item_updated', 'items.show_details', 'items.show'];
    public const ITEM_UPDATED_QUANTITY = ['item_updated_quantity', 'items.show_details', 'items.show'];
    public const ITEM_DELETED = ['item_deleted', 'items.show_details', 'items.show'];
    public const PAGE_CREATED = ['page_created', 'pages.show_details', 'pages.show'];
    public const PAGE_UPDATED = ['page_updated', 'pages.show_details', 'pages.show'];
    public const PAGE_DELETED = ['page_deleted', 'pages.show_details', 'pages.show'];
    public const PRODUCT_SET_CREATED = ['product_set_created', 'product_sets.show_details', 'product_sets.show'];
    public const PRODUCT_SET_UPDATED = ['product_set_updated', 'product_sets.show_details', 'product_sets.show'];
    public const PRODUCT_SET_DELETED = ['product_set_deleted', 'product_sets.show_details', 'product_sets.show'];
    public const USER_CREATED = ['user_created', 'users.show_details', 'users.show'];
    public const USER_UPDATED = ['user_updated', 'users.show_details', 'users.show'];
    public const USER_DELETED = ['user_deleted', 'users.show_details', 'users.show'];
    public const DISCOUNT_CREATED = ['discount_created', 'discounts.show_details', 'discounts.show'];
    public const DISCOUNT_UPDATED = ['discount_updated', 'discounts.show_details', 'discounts.show'];
    public const DISCOUNT_DELETED = ['discount_deleted', 'discounts.show_details', 'discounts.show'];

    public static function getPermissionsByEventName(string $event): array
    {
        return array_slice(self::coerce(Str::upper(Str::snake($event)))->value, 1);
    }

    public static function getRandomCamelCaseKey(): string
    {
        return self::getCamelCaseKey(self::getRandomKey());
    }

    public static function getEventList(): array
    {
        $events = self::getInstances();
        $result = [];
        foreach ($events as $event) {
            array_push($result, self::getData($event));
        }

        return $result;
    }

    public static function getDescription($value): string
    {
        return parent::getDescription($value[0]);
    }

    private static function getData(Enum $enum): array
    {
        return [
            'key' => self::getCamelCaseKey($enum->key),
            'name' => self::getFriendlyKeyName($enum->key),
            'description' => $enum->description,
        ];
    }

    private static function getCamelCaseKey(string $key): string
    {
        return Str::ucfirst(Str::camel(Str::lower($key)));
    }
}
