<?php

namespace App\Enums;

use App\Traits\EnumUtilities;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

enum EventType : string
{
    use EnumUtilities;

    case ORDER_CREATED = 'OrderCreated';
    case ORDER_UPDATED = 'OrderUpdated';
    case ORDER_UPDATED_STATUS = 'OrderUpdatedStatus';
    case PRODUCT_CREATED = 'ProductCreated';
    case PRODUCT_UPDATED = 'ProductUpdated';
    case PRODUCT_DELETED = 'ProductDeleted';
    case ITEM_CREATED = 'ItemCreated';
    case ITEM_UPDATED = 'ItemUpdated';
    case ITEM_UPDATED_QUANTITY = 'ItemUpdatedQuantity';
    case ITEM_DELETED = 'ItemDeleted';
    case PAGE_CREATED = 'PageCreated';
    case PAGE_UPDATED = 'PageUpdated';
    case PAGE_DELETED = 'PageDeleted';
    case PRODUCT_SET_CREATED = 'ProductSetCreated';
    case PRODUCT_SET_UPDATED = 'ProductSetUpdated';
    case PRODUCT_SET_DELETED = 'ProductSetDeleted';
    case USER_CREATED = 'UserCreated';
    case USER_UPDATED = 'UserUpdated';
    case USER_DELETED = 'UserDeleted';
    case SALE_CREATED = 'SaleCreated';
    case SALE_UPDATED = 'SaleUpdated';
    case SALE_DELETED = 'SaleDeleted';
    case COUPON_CREATED = 'CouponCreated';
    case COUPON_UPDATED = 'CouponUpdated';
    case COUPON_DELETED = 'CouponDeleted';
    case TFA_INIT = 'TfaInit';
    case TFA_SECURITY_CODE = 'TfaSecurityCode';
    case TFA_RECOVERY_CODES_CHANGED = 'TfaRecoveryCodesChanged';
    case PASSWORD_RESET = 'PasswordReset';
    case SUCCESSFUL_LOGIN_ATTEMPT = 'SuccessfulLoginAttempt';
    case NEW_LOCALIZATION_LOGIN_ATTEMPT = 'NewLocalizationLoginAttempt';
    case FAILED_LOGIN_ATTEMPT = 'FailedLoginAttempt';
    case ADD_ORDER_DOCUMENT = 'AddOrderDocument';
    case REMOVE_ORDER_DOCUMENT = 'RemoveOrderDocument';
    case ORDER_UPDATED_PAID = 'OrderUpdatedPaid';
    case ORDER_UPDATED_SHIPPING_NUMBER = 'OrderUpdatedShippingNumber';

    public static function securedEvents(): array
    {
        return [
            self::TFA_INIT,
            self::TFA_SECURITY_CODE,
            self::TFA_RECOVERY_CODES_CHANGED,
            self::PASSWORD_RESET,
            self::SUCCESSFUL_LOGIN_ATTEMPT,
            self::NEW_LOCALIZATION_LOGIN_ATTEMPT,
            self::FAILED_LOGIN_ATTEMPT,
        ];
    }

    public static function getEventList(): array
    {
        $events = self::cases();
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

    private static function getFriendlyKeyName(string $key): string
    {
        if (ctype_upper(preg_replace('/[^a-zA-Z]/', '', $key))) {
            $key = strtolower($key);
        }

        return ucfirst(str_replace('_', ' ', Str::snake($key)));
    }

    private static function getDescription(string $key): string
    {
        return Lang::get('enums')[self::class][$key];
    }

    private static function getData(EventType $enum, mixed $permissions, mixed $hidden_permissions): array
    {
        return [
            'key' => $enum->value,
            'name' => self::getFriendlyKeyName($enum->name),
            'description' => self::getDescription($enum->value),
            'required_permissions' => $permissions,
            'required_hidden_permissions' => $hidden_permissions,
            'encrypted' => in_array($enum->value, self::securedEvents()),
        ];
    }
}
