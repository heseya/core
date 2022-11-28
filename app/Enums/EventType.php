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
    public const ORDER_REQUESTED_SHIPPING = 'OrderRequestedShipping';
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
    public const SALE_CREATED = 'SaleCreated';
    public const SALE_UPDATED = 'SaleUpdated';
    public const SALE_DELETED = 'SaleDeleted';
    public const COUPON_CREATED = 'CouponCreated';
    public const COUPON_UPDATED = 'CouponUpdated';
    public const COUPON_DELETED = 'CouponDeleted';
    public const TFA_INIT = 'TfaInit';
    public const TFA_SECURITY_CODE = 'TfaSecurityCode';
    public const TFA_RECOVERY_CODES_CHANGED = 'TfaRecoveryCodesChanged';
    public const PASSWORD_RESET = 'PasswordReset';
    public const SUCCESSFUL_LOGIN_ATTEMPT = 'SuccessfulLoginAttempt';
    public const NEW_LOCALIZATION_LOGIN_ATTEMPT = 'NewLocalizationLoginAttempt';
    public const FAILED_LOGIN_ATTEMPT = 'FailedLoginAttempt';
    public const ADD_ORDER_DOCUMENT = 'AddOrderDocument';
    public const REMOVE_ORDER_DOCUMENT = 'RemoveOrderDocument';
    public const ORDER_UPDATED_PAID = 'OrderUpdatedPaid';
    public const ORDER_UPDATED_SHIPPING_NUMBER = 'OrderUpdatedShippingNumber';
    public const SEND_ORDER_URLS = 'SendOrderUrls';

    public static array $securedEvents = [
        self::TFA_INIT,
        self::TFA_SECURITY_CODE,
        self::TFA_RECOVERY_CODES_CHANGED,
        self::PASSWORD_RESET,
        self::SUCCESSFUL_LOGIN_ATTEMPT,
        self::NEW_LOCALIZATION_LOGIN_ATTEMPT,
        self::FAILED_LOGIN_ATTEMPT,
    ];

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

    private static function getData(Enum $enum, mixed $permissions, mixed $hidden_permissions): array
    {
        return [
            'key' => $enum->value,
            'name' => self::getFriendlyKeyName($enum->key),
            'description' => $enum->description,
            'required_permissions' => $permissions,
            'required_hidden_permissions' => $hidden_permissions,
            'encrypted' => in_array($enum->value, self::$securedEvents),
        ];
    }
}
