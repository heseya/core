<?php

namespace App\Enums\ExceptionsEnums;

use BenSampo\Enum\Enum;

final class Exceptions extends Enum
{
    public const CLIENT_INVALID_INSTALLATION_RESPONSE = 'App has invalid installation response';
    public const CLIENT_FAILED_TO_CONNECT_WITH_APP = 'Failed to connect with application';
    public const CLIENT_FAILED_TO_UNINSTALL_APP =
        'Failed to uninstall the application. Failed response from app. Check if uninstall token is correct';
    public const CLIENT_ASSIGN_INVALID_PERMISSIONS =
        'Assigning invalid permissions. One or more of requested permissions doesn\'t exist';
    public const CLIENT_ADD_APP_WITH_PERMISSIONS_USER_DONT_HAVE = 'Can\'t add an app with permissions you don\'t have';
    public const CLIENT_APP_INFO_RESPONDED_WITH_INVALID_CODE = 'Application info responded with invalid status code';
    public const CLIENT_APP_INSTALLATION_RESPONDED_WITH_INVALID_CODE =
        'Application installation responded with invalid status code';
    public const CLIENT_APP_RESPONDED_WITH_INVALID_INFO = 'App responded with invalid info';
    public const CLIENT_APP_WANTS_INVALID_PERMISSION = 'App wants invalid permissions';
    public const CLIENT_ADD_APP_WITHOUT_REQUIRED_PERMISSIONS = 'Can\'t add app without all required permissions';
    public const CLIENT_ADD_PERMISSION_APP_DOESNT_WANT = 'Can\'t add any permissions application doesn\'t want';
    public const CLIENT_MODEL_NOT_AUDITABLE = 'Model not auditable';
    public const CLIENT_INVALID_CREDENTIALS = 'Invalid credentials';
    public const CLIENT_INVALID_PASSWORD = 'Invalid password';
    public const CLIENT_INVALID_TOKEN = 'Invalid token';
    public const CLIENT_INVALID_IDENTITY_TOKEN = 'Invalid identity token';
    public const CLIENT_USER_DOESNT_EXIST = 'User doesn\'t exist';
    public const CLIENT_TOKEN_INVALID_OR_INACTIVE =
        'The token is invalid or inactive. Try to reset your password again';

    public const CLIENT_DISCOUNT_TYPE_NOT_SUPPORTED =
        'Discount type is not supported, discount value cannot be calculated';
    public const CLIENT_CANNOT_APPLY_SELECTED_DISCOUNT_TYPE = 'Cannot apply selected discount type to order';
    public const CLIENT_NOT_ENOUGH_ITEMS = 'There is not enough items to order the product';
    public const CLIENT_ITEM_NOT_FOUND = 'Item not found when creating an order';
    public const CLIENT_ORDER_CODE_LENGTH_MUST_BE_NUMERIC = 'Order code length in config must be numeric';

    public const CLIENT_CREATE_ROLE_WITHOUT_PERMISSION = 'Cant create a role with permissions you don\'t have';
    public const CLIENT_UPDATE_ROLE_WITHOUT_PERMISSION = 'Cant update a role with permissions you don\'t have';
    public const CLIENT_DELETE_ROLE_WITHOUT_PERMISSION = 'Cant delete a role with permissions you don\'t have';
    public const CLIENT_UPDATE_OWNER_PERMISSION = 'Can\'t update owners permissions';
    public const CLIENT_DELETE_BUILT_IN_ROLE = 'Can\'t delete built-in roles';
    public const CLIENT_GIVE_ROLE_THAT_USER_DOESNT_HAVE =
        'Can\'t give a role with permissions you don\'t have to the user';
    public const CLIENT_REMOVE_ROLE_THAT_USER_DOESNT_HAVE =
        'Can\'t remove a role with permissions you don\'t have from the user';
    public const CLIENT_ONLY_OWNER_GRANTS_OWNER_ROLE = 'Only owner can grant the owner role';
    public const CLIENT_ONLY_OWNER_REMOVES_OWNER_ROLE = 'Only owner can remove the owner role';
    public const CLIENT_ONE_OWNER_REMAINS = 'There must always be at least one Owner left';
    public const CLIENT_DELETE_WHEN_RELATION_EXISTS = 'Element can\'t be deleted, because it has relations';

    public const CLIENT_ORDER_EDIT_ERROR = 'Error in order update transaction. Check order and addresses data';
    public const CLIENT_CHANGE_CANCELED_ORDER_STATUS = 'Cannot change the status of a cancelled order';
    public const CLIENT_UNKNOWN_STATUS = 'Unknown order status';
    public const CLIENT_MODEL_NOT_SORTABLE = 'Model is not sortable';
    public const CLIENT_ORDER_PAID = 'Order is already paid';
    public const CLIENT_UNKNOWN_PAYMENT_METHOD = 'Unknown payment method';
    public const CLIENT_INVALID_PAYMENT = 'Payment signature hash isn\'t correct hash';
    public const CLIENT_GENERATE_PAYMENT_URL = 'Cannot generate payment url';
    public const CLIENT_VERIFY_PAYMENT = 'Cannot verify payment';

    public const CLIENT_UNTRUSTED_NOTIFICATION = 'Cannot verify payment\'s signature';

    public const CLIENT_NO_REQUIRED_PERMISSIONS_TO_EVENTS =
        'Client has no required permissions to perform any action on this event';

    public const CLIENT_DOESNT_HAVE_TFA_TYPE_SELECTED = 'Client does not have 2FA type selected';
    public const CLIENT_TFA_CANNOT_REMOVE = 'You cannot remove 2FA yourself in this way';
    public const CLIENT_TFA_REQUIRED = 'Two-Factor Authentication code is required in request';
    public const CLIENT_ONLY_USER_CAN_SET_TFA = 'Only users can set up Two-Factor Authentication';
    public const CLIENT_INVALID_TFA_TYPE = 'Invalid Two-Factor Authentication type';
    public const CLIENT_TFA_INVALID_TOKEN = 'Invalid Two-Factor Authentication token';
    public const CLIENT_TFA_NOT_SET_UP = 'Two-Factor Authentication is not setup';
    public const CLIENT_TFA_ALREADY_SET_UP = 'Two-Factor Authentication is already setup';

    public const CLIENT_WEBHOOK_USER_ACTION = 'Only user can use this method on this webhook';
    public const CLIENT_WEBHOOK_APP_ACTION = 'Only application can use this method on this webhook';

    public const CLIENT_APPS_NO_ACCESS = 'Applications cannot access this endpoint';
    public const CLIENT_NO_ACCESS_TO_DOWNLOAD_DOCUMENT = 'No access';

    public const CLIENT_REMOVE_DEFAULT_ADDRESS = 'You cannot delete default address';
    public const CLIENT_STATUS_USED = 'Can\'t update or remove status that is currently used in order';

    public const CLIENT_SHIPPING_METHOD_NOT_OWNER = 'This shipping method belongs to other application';

    public const CLIENT_SHIPPING_METHOD_INVALID_TYPE = 'Shipping method or digital shipping method type is invalid';

    public const CDN_NOT_ALLOWED_TO_CHANGE_ALT = 'You cannot change alt attribute of this image';

    public const SERVER_CDN_ERROR = 'CDN responded with an error';
    public const SERVER_ERROR = 'Server responded with an error';
    public const SERVER_ORDER_STATUSES_NOT_CONFIGURED = 'Order statuses are not configured';
    public const SERVER_TRANSACTION_ERROR = 'Unexpected error occurred during the database transaction.';

    public const ORDER_NOT_ENOUGH_ITEMS_IN_WAREHOUSE = 'Not every item is available';
    public const ORDER_SHIPPING_METHOD_TYPE_MISMATCH = 'Selected shipping methods don\'t match selected product types';

    public const PRODUCT_IS_NOT_ON_WISHLIST = 'Product is not on wishlist';
    public const PRODUCT_SET_IS_NOT_ON_FAVOURITES_LIST = 'Product set is not on favourites list';

    public const PRODUCT_PURCHASE_LIMIT = 'The limit of purchased product units per user has been exceeded';

    public const PAYMENT_METHOD_NOT_AVAILABLE_FOR_SHIPPING =
        'Payment method not available for selected shipping method';

    public static function getCode(string $value): int
    {
        return match ($value) {
            self::CLIENT_DISCOUNT_TYPE_NOT_SUPPORTED,
            self::CLIENT_DELETE_WHEN_RELATION_EXISTS,
            self::CLIENT_MODEL_NOT_AUDITABLE,
            self::CLIENT_APPS_NO_ACCESS => 400,
            self::CLIENT_TFA_REQUIRED,
            self::CLIENT_WEBHOOK_APP_ACTION,
            self::CLIENT_WEBHOOK_USER_ACTION => 403,
            self::SERVER_CDN_ERROR,
            self::SERVER_ERROR,
            self::SERVER_ORDER_STATUSES_NOT_CONFIGURED => 500,
            default => 422
        };
    }
}
