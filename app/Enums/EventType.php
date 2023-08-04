<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

enum EventType: string
{
    use EnumTrait;

    case ADD_ORDER_DOCUMENT = 'AddOrderDocument';
    case COUPON_CREATED = 'CouponCreated';
    case COUPON_DELETED = 'CouponDeleted';
    case COUPON_UPDATED = 'CouponUpdated';
    case DISCOUNT_CREATED = 'DiscountCreated';
    case DISCOUNT_DELETED = 'DiscountDeleted';
    case DISCOUNT_UPDATED = 'DiscountUpdated';
    case FAILED_LOGIN_ATTEMPT = 'FailedLoginAttempt';
    case ITEM_CREATED = 'ItemCreated';
    case ITEM_DELETED = 'ItemDeleted';
    case ITEM_UPDATED = 'ItemUpdated';
    case ITEM_UPDATED_QUANTITY = 'ItemUpdatedQuantity';
    case LANGUAGE_CREATED = 'LanguageCreated';
    case LANGUAGE_DELETED = 'LanguageDeleted';
    case LANGUAGE_UPDATED = 'LanguageUpdated';
    case NEW_LOCALIZATION_LOGIN_ATTEMPT = 'NewLocalizationLoginAttempt';
    case ORDER_CREATED = 'OrderCreated';
    case ORDER_UPDATED = 'OrderUpdated';
    case ORDER_UPDATED_PAID = 'OrderUpdatedPaid';
    case ORDER_UPDATED_SHIPPING_NUMBER = 'OrderUpdatedShippingNumber';
    case ORDER_UPDATED_STATUS = 'OrderUpdatedStatus';
    case PAGE_CREATED = 'PageCreated';
    case PAGE_DELETED = 'PageDeleted';
    case PAGE_UPDATED = 'PageUpdated';
    case PASSWORD_RESET = 'PasswordReset';
    case PRODUCT_CREATED = 'ProductCreated';
    case PRODUCT_DELETED = 'ProductDeleted';
    case PRODUCT_PRICE_UPDATED = 'ProductPriceUpdated';
    case PRODUCT_SET_CREATED = 'ProductSetCreated';
    case PRODUCT_SET_DELETED = 'ProductSetDeleted';
    case PRODUCT_SET_UPDATED = 'ProductSetUpdated';
    case PRODUCT_UPDATED = 'ProductUpdated';
    case REMOVE_ORDER_DOCUMENT = 'RemoveOrderDocument';
    case SALE_CREATED = 'SaleCreated';
    case SALE_DELETED = 'SaleDeleted';
    case SALE_UPDATED = 'SaleUpdated';
    case SEND_ORDER_URLS = 'SendOrderUrls';
    case SUCCESSFUL_LOGIN_ATTEMPT = 'SuccessfulLoginAttempt';
    case TFA_INIT = 'TfaInit';
    case TFA_RECOVERY_CODES_CHANGED = 'TfaRecoveryCodesChanged';
    case TFA_SECURITY_CODE = 'TfaSecurityCode';
    case USER_CREATED = 'UserCreated';
    case USER_DELETED = 'UserDeleted';
    case USER_UPDATED = 'UserUpdated';

    public const SECURED_EVENTS = [
        self::FAILED_LOGIN_ATTEMPT,
        self::NEW_LOCALIZATION_LOGIN_ATTEMPT,
        self::PASSWORD_RESET,
        self::SUCCESSFUL_LOGIN_ATTEMPT,
        self::TFA_INIT,
        self::TFA_RECOVERY_CODES_CHANGED,
        self::TFA_SECURITY_CODE,
    ];

    public static function getEventList(): array
    {
        $events = self::cases();
        $result = [];

        $required_permissions = Config::get('events.permissions');
        $required_hidden_permissions = Config::get('events.permissions_hidden');

        foreach ($events as $event) {
            $hidden_permissions = array_key_exists($event->value, $required_hidden_permissions)
                ? $required_hidden_permissions[$event->value]
                : [];
            $result[] = $event->getData($required_permissions[$event->value], $hidden_permissions);
        }

        return $result;
    }

    public function getFriendlyName(): string
    {
        $name = $this->name;

        if (ctype_upper(preg_replace('/[^a-zA-Z]/', '', $name))) {
            $name = mb_strtolower($name);
        }

        return ucfirst(str_replace('_', ' ', Str::snake($name)));
    }

    public function getLocalizedDescription(): ?string
    {
        $localizedStringKey = 'enums.' . self::class . '.' . $this->value;

        if (Lang::has($localizedStringKey)) {
            return __($localizedStringKey);
        }

        return $this->getFriendlyName();
    }

    public function getData(mixed $permissions, mixed $hidden_permissions): array
    {
        return [
            'key' => $this->value,
            'name' => $this->getFriendlyName(),
            'description' => $this->getLocalizedDescription(),
            'required_permissions' => $permissions,
            'required_hidden_permissions' => $hidden_permissions,
            'encrypted' => in_array($this, self::SECURED_EVENTS),
        ];
    }
}
