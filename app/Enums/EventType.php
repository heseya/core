<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;
use Illuminate\Support\Facades\Config;

final class EventType extends Enum implements LocalizedEnum
{
    public const ORDER_CREATED = 'OrderCreated';
    public const ORDER_UPDATED = 'OrderUpdated';
    public const ORDER_UPDATED_STATUS = 'OrderUpdatedStatus';
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

        $required_permissions = Config::get('events.permissions');
        $required_hidden_permissions = Config::get('events.permissions_hidden');

        foreach ($events as $event) {
            $hidden_permissions = array_key_exists($event->value, $required_hidden_permissions)
                ? $required_hidden_permissions[$event->value] : [];
            array_push($result, self::getData($event, $required_permissions[$event->value], $hidden_permissions));
        }

        return $result;
    }

    private static function getData(Enum $enum, $permissions, $hidden_permissions): array
    {
        return [
            'key' => $enum->value,
            'name' => self::getFriendlyKeyName($enum->key),
            'description' => $enum->description,
            'required_permissions' => $permissions,
            'required_hidden_permissions' => $hidden_permissions,
        ];
    }
}