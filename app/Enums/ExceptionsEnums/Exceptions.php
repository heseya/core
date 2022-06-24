<?php

namespace App\Enums\ExceptionsEnums;

use App\Traits\EnumUtilities;

enum Exceptions: string
{
    use EnumUtilities;

    case CLIENT_INVALID_INSTALLATION_RESPONSE = 'App has invalid installation response';
    case CLIENT_FAILED_TO_CONNECT_WITH_APP = 'Failed to connect with application';
    case CLIENT_FAILED_TO_UNINSTALL_APP = 'Failed to uninstall the application. Failed response from app';
    case CLIENT_ASSIGN_INVALID_PERMISSIONS =
        'Assigning invalid permissions, there is no difference between all possible permissions and assigned ones';
    case CLIENT_ADD_APP_WITH_PERMISSIONS_USER_DONT_HAVE = 'Can\'t add an app with permissions you don\'t have';
    case CLIENT_APP_RESPONDED_WITH_INVALID_CODE = 'Application info responded with invalid status code';
    case CLIENT_APP_RESPONDED_WITH_INVALID_INFO = 'App responded with invalid info';
    case CLIENT_APP_WANTS_INVALID_PERMISSION = 'App wants invalid permissions';
    case CLIENT_ADD_APP_WITHOUT_REQUIRED_PERMISSIONS = 'Can\'t add app without all required permissions';
    case CLIENT_ADD_PERMISSION_APP_DOESNT_WANT = 'Can\'t add any permissions application doesn\'t want';
    case CLIENT_MODEL_NOT_AUDITABLE = 'Model not auditable';
    case CLIENT_INVALID_CREDENTIALS = 'Invalid credentials';
    case CLIENT_INVALID_PASSWORD = 'Invalid password';
    case CLIENT_INVALID_TOKEN = 'Invalid token';
    case CLIENT_INVALID_IDENTITY_TOKEN = 'Invalid identity token';
    case CLIENT_USER_DOESNT_EXIST = 'User doesn\'t exist';
    case CLIENT_TOKEN_INVALID_OR_INACTIVE =
        'The token is invalid or inactive. Try to reset your password again';

    case CLIENT_DISCOUNT_TYPE_NOT_SUPPORTED =
        'Discount type is not supported, discount value cannot be calculated.';
    case CLIENT_CANNOT_APPLY_SELECTED_DISCOUNT_TYPE = 'Cannot apply selected discount type to order';
    case CLIENT_NOT_ENOUGH_ITEMS = 'There is not enough items to order the product';
    case CLIENT_ITEM_NOT_FOUND = 'Item not found';
    case CLIENT_NON_NUMERIC_VALUE = 'Value must be numeric';

    case CLIENT_CREATE_ROLE_WITHOUT_PERMISSION = 'Cant create a role with permissions you don\'t have';
    case CLIENT_UPDATE_ROLE_WITHOUT_PERMISSION = 'Cant update a role with permissions you don\'t have';
    case CLIENT_DELETE_ROLE_WITHOUT_PERMISSION = 'Cant delete a role with permissions you don\'t have';
    case CLIENT_UPDATE_OWNER_PERMISSION = 'Can\'t update owners permissions';
    case CLIENT_DELETE_BUILT_IN_ROLE = 'Can\'t delete built-in roles';
    case CLIENT_GIVE_ROLE_THAT_USER_DOESNT_HAVE =
        'Can\'t give a role with permissions you don\'t have to the user';
    case CLIENT_REMOVE_ROLE_THAT_USER_DOESNT_HAVE =
        'Can\'t remove a role with permissions you don\'t have from the user';
    case CLIENT_ONLY_OWNER_GRANTS_OWNER_ROLE = 'Only owner can grant the owner role';
    case CLIENT_ONLY_OWNER_REMOVES_OWNER_ROLE = 'Only owner can remove the owner role';
    case CLIENT_ONE_OWNER_REMAINS = 'There must always be at least one Owner left';
    case CLIENT_DELETE_WHEN_RELATION_EXISTS = 'Element can\'t be deleted, because it has relations';

    case CLIENT_ORDER_EDIT_ERROR = 'Error while editing order';
    case CLIENT_CHANGE_CANCELED_ORDER_STATUS = 'Cannot change the status of a cancelled order';
    case CLIENT_MODEL_NOT_SORTABLE = 'Model is not sortable';
    case CLIENT_ORDER_PAID = 'Order is already paid';
    case CLIENT_UNKNOWN_PAYMENT_METHOD = 'Unknown payment method';
    case CLIENT_INVALID_PAYMENT = 'Payment\'s signature doesn\'t match correct signature';
    case CLIENT_GENERATE_PAYMENT_URL = 'Cannot generate payment url';
    case CLIENT_VERIFY_PAYMENT = 'Cannot verify payment';

    case CLIENT_UNTRUSTED_NOTIFICATION = 'Cannot verify payment\'s signature';

    case CLIENT_NO_REQUIRED_PERMISSIONS_TO_EVENTS =
        'Client has no required permissions to perform any action on this event';

    case CLIENT_DOESNT_HAVE_TFA_TYPE_SELECTED = 'Client does not have 2FA type selected';
    case CLIENT_TFA_CANNOT_REMOVE = 'You cannot remove 2FA yourself in this way';
    case CLIENT_TFA_REQUIRED = 'Two-Factor Authentication is required';
    case CLIENT_ONLY_USER_CAN_SET_TFA = 'Only users can set up Two-Factor Authentication';
    case CLIENT_INVALID_TFA_TYPE = 'Invalid Two-Factor Authentication type';
    case CLIENT_TFA_INVALID_TOKEN = 'Invalid Two-Factor Authentication token';
    case CLIENT_TFA_NOT_SET_UP = 'Two-Factor Authentication is not setup';
    case CLIENT_TFA_ALREADY_SET_UP = 'Two-Factor Authentication is already setup';

    case CLIENT_WEBHOOK_USER_ACTION = 'Only user can use this method on this webhook';
    case CLIENT_WEBHOOK_APP_ACTION = 'Only application can use this method on this webhook';

    case CLIENT_APPS_NO_ACCESS = 'Applications cannot access this endpoint';
    case CLIENT_NO_ACCESS_TO_DOWNLOAD_DOCUMENT = 'No access';

    case CLIENT_REMOVE_DEFAULT_ADDRESS = 'You cannot delete default address';
    case CLIENT_STATUS_USED = 'Can\'t update or remove status that is currently used in order';

    case SERVER_CDN_ERROR = 'CDN responded with an error';
    case SERVER_ERROR = 'Server responded with an error';
    case SERVER_ORDER_STATUSES_NOT_CONFIGURED = 'Order statuses are not configured';

    case ORDER_NOT_ENOUGH_ITEMS_IN_WAREHOUSE = 'Not every item is available';

    public static function getCode(string $value): int
    {
        return match ($value) {
            self::CLIENT_DISCOUNT_TYPE_NOT_SUPPORTED->value,
            self::CLIENT_DELETE_WHEN_RELATION_EXISTS->value,
            self::CLIENT_MODEL_NOT_AUDITABLE->value,
            self::CLIENT_APPS_NO_ACCESS->value => 400,
            self::CLIENT_TFA_REQUIRED->value,
            self::CLIENT_WEBHOOK_APP_ACTION->value,
            self::CLIENT_WEBHOOK_USER_ACTION->value=> 403,
            self::SERVER_CDN_ERROR->value,
            self::SERVER_ERROR->value,
            self::SERVER_ORDER_STATUSES_NOT_CONFIGURED->value => 500,
            default => 422
        };
    }
}
