<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class EventType extends Enum implements LocalizedEnum
{
    public const ORDER_CREATED = 'OrderCreated';
    public const ORDER_UPDATED = 'OrderUpdated';
    public const ORDER_DELETED = 'OrderDeleted';
    public const PRODUCT_CREATED = 'ProductCreated';
    public const PRODUCT_UPDATED = 'ProductUpdated';
    public const PRODUCT_DELETED = 'ProductDeleted';
    public const ITEM_CREATED = 'ItemCreated';
    public const ITEM_UPDATED = 'ItemUpdated';
    public const ITEM_UPDATED_QUANTITY = 'ItemUpdatedQuantity';
    public const ITEM_DELETED = 'ItemDeleted';
    public const PAGE_CREATED = 'PageCreated';
    public const PAGE_UPDATED = 'PageUpdated';
    public const PAGE_DELETED = 'PageDeleted';
    public const PRODUCT_SET_CREATED = 'ProductSetCreated';
    public const PRODUCT_SET_UPDATED = 'ProductSetUpdated';
    public const PRODUCT_SET_DELETED = 'ProductSetDeleted';
    public const USER_CREATED = 'UserCreated';
    public const USER_UPDATED = 'UserUpdated';
    public const USER_DELETED = 'UserDeleted';
    public const DISCOUNT_CREATED = 'DiscountCreated';
    public const DISCOUNT_UPDATED = 'DiscountUpdated';
    public const DISCOUNT_DELETED = 'DiscountDeleted';

    public static function getEventList(): array
    {
        $events = self::getInstances();
        $result = [];
        foreach ($events as $event) {
            array_push($result, self::getData($event));
        }

        return $result;
    }

    private static function getData(Enum $enum): array
    {
        return [
            'key' => $enum->value,
            'name' => self::getFriendlyKeyName($enum->key),
            'description' => $enum->description,
        ];
    }
}
