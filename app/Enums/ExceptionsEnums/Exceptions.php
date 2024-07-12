<?php

namespace App\Enums\ExceptionsEnums;

use App\Enums\Traits\EnumTrait;

enum Exceptions: string
{
    use EnumTrait;

    case CLIENT_INVALID_INSTALLATION_RESPONSE = 'App has invalid installation response';
    case CLIENT_FAILED_TO_CONNECT_WITH_APP = 'Failed to connect with application';
    case CLIENT_FAILED_TO_UNINSTALL_APP = 'Failed to uninstall the application. Failed response from app. Check if uninstall token is correct';
    case CLIENT_ASSIGN_INVALID_PERMISSIONS = 'Assigning invalid permissions. One or more of requested permissions doesn\'t exist';
    case CLIENT_ADD_APP_WITH_PERMISSIONS_USER_DONT_HAVE = 'Can\'t add an app with permissions you don\'t have';
    case CLIENT_APP_INFO_RESPONDED_WITH_INVALID_CODE = 'Application info responded with invalid status code';
    case CLIENT_APP_INSTALLATION_RESPONDED_WITH_INVALID_CODE = 'Application installation responded with invalid status code';
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
    case CLIENT_TOKEN_INVALID_OR_INACTIVE = 'The token is invalid or inactive. Try to reset your password again';

    case CLIENT_DISCOUNT_TYPE_NOT_SUPPORTED = 'Discount type is not supported, discount value cannot be calculated';
    case CLIENT_CANNOT_APPLY_SELECTED_DISCOUNT_TYPE = 'Cannot apply selected discount type to order';
    case CLIENT_NOT_ENOUGH_ITEMS = 'There is not enough items to order the product';
    case CLIENT_ITEM_NOT_FOUND = 'Item not found when creating an order';
    case CLIENT_ORDER_CODE_LENGTH_MUST_BE_NUMERIC = 'Order code length in config must be numeric';

    case CLIENT_CREATE_ROLE_WITHOUT_PERMISSION = 'Cant create a role with permissions you don\'t have';
    case CLIENT_UPDATE_ROLE_WITHOUT_PERMISSION = 'Cant update a role with permissions you don\'t have';
    case CLIENT_DELETE_ROLE_WITHOUT_PERMISSION = 'Cant delete a role with permissions you don\'t have';
    case CLIENT_UPDATE_OWNER_PERMISSION = 'Can\'t update owners permissions';
    case CLIENT_DELETE_BUILT_IN_ROLE = 'Can\'t delete built-in roles';
    case CLIENT_GIVE_ROLE_THAT_USER_DOESNT_HAVE = 'Can\'t give a role with permissions you don\'t have to the user';
    case CLIENT_REMOVE_ROLE_THAT_USER_DOESNT_HAVE = 'Can\'t remove a role with permissions you don\'t have from the user';

    case CLIENT_REGISTER_WITH_NON_REGISTRATION_ROLE = 'Can\'t register with a non registration role.';
    case CLIENT_CONSENT_NOT_EXISTS = 'Consent not exists';
    case CLIENT_NOT_ACCEPTED_ALL_REQUIRED_CONSENTS = 'You must accept the required consents.';
    case CLIENT_ONLY_OWNER_GRANTS_OWNER_ROLE = 'Only owner can grant the owner role';
    case CLIENT_ONLY_OWNER_REMOVES_OWNER_ROLE = 'Only owner can remove the owner role';
    case CLIENT_ONE_OWNER_REMAINS = 'There must always be at least one Owner left';
    case CLIENT_ONE_SALES_CHANNEL_REMAINS = 'There must always be at least one sales channel left';
    case CLIENT_DELETE_WHEN_RELATION_EXISTS = 'Element can\'t be deleted, because it has relations';

    case CLIENT_ORDER_EDIT_ERROR = 'Error in order update transaction. Check order and addresses data';
    case CLIENT_CHANGE_CANCELED_ORDER_STATUS = 'Cannot change the status of a cancelled order';
    case CLIENT_UNKNOWN_STATUS = 'Unknown order status';
    case CLIENT_MODEL_NOT_SORTABLE = 'Model is not sortable';
    case CLIENT_ORDER_PAID = 'Order is already paid';
    case CLIENT_UNKNOWN_PAYMENT_METHOD = 'Unknown payment method';
    case CLIENT_INVALID_PAYMENT = 'Payment signature hash isn\'t correct hash';
    case CLIENT_GENERATE_PAYMENT_URL = 'Cannot generate payment url';
    case CLIENT_VERIFY_PAYMENT = 'Cannot verify payment';

    case CLIENT_UNTRUSTED_NOTIFICATION = 'Cannot verify payment\'s signature';

    case CLIENT_NO_REQUIRED_PERMISSIONS_TO_EVENTS = 'Client has no required permissions to perform any action on this event';

    case CLIENT_DOESNT_HAVE_TFA_TYPE_SELECTED = 'Client does not have 2FA type selected';
    case CLIENT_TFA_CANNOT_REMOVE = 'You cannot remove 2FA yourself in this way';
    case CLIENT_TFA_REQUIRED = 'Two-Factor Authentication code is required in request';
    case CLIENT_ONLY_USER_CAN_SET_TFA = 'Only users can set up Two-Factor Authentication';
    case CLIENT_INVALID_TFA_TYPE = 'Invalid Two-Factor Authentication type';
    case CLIENT_TFA_INVALID_TOKEN = 'Invalid Two-Factor Authentication token';
    case CLIENT_TFA_NOT_SET_UP = 'Two-Factor Authentication is not setup';
    case CLIENT_TFA_ALREADY_SET_UP = 'Two-Factor Authentication is already setup';

    case CLIENT_WEBHOOK_USER_ACTION = 'Only user can use this method on this webhook';
    case CLIENT_WEBHOOK_APP_ACTION = 'Only application can use this method on this webhook';

    case CLIENT_APPS_NO_ACCESS = 'Applications cannot access this endpoint';
    case CLIENT_USERS_NO_ACCESS = 'Users cannot access this endpoint';
    case CLIENT_NO_ACCESS = 'No access';

    case CLIENT_REMOVE_DEFAULT_ADDRESS = 'You cannot delete default address';
    case CLIENT_STATUS_USED = 'Can\'t update or remove status that is currently used in order';

    case CLIENT_SHIPPING_METHOD_NOT_OWNER = 'This shipping method belongs to other application';
    case CLIENT_SHIPPING_METHOD_INVALID_TYPE = 'Shipping method or digital shipping method type is invalid';
    case CLIENT_SHIPPING_METHOD_INVALID_COUNTRY = 'Selected shipping method not available for recipient country';
    case CLIENT_SHIPPING_METHOD_NOT_EXISTS = 'Shipping method does not exist.';
    case CLIENT_SHIPPING_POINT_NOT_EXISTS = 'Shipping point does not exists.';
    case CLIENT_SHIPPING_POINT_STRING = 'Shipping point should be string.';
    case CLIENT_SHIPPING_ADDRESS_INVALID = 'Shipping address in invalid.';

    case CDN_NOT_ALLOWED_TO_CHANGE_ALT = 'You cannot change alt attribute of this image';

    case CLIENT_PROVIDER_IS_NOT_ACTIVE = 'Chosen auth provider is not active';
    case CLIENT_PROVIDER_NOT_FOUND = 'Provider cannot be found';

    case CLIENT_INVALID_EXCLUDED_MODEL = 'Invalid excluded model';

    case CLIENT_DUPLICATED_DEFAULT_LANGUAGE = 'There must be exactly one default language.';
    case CLIENT_DELETE_DEFAULT_LANGUAGE = 'You cannot delete the default language.';
    case CLIENT_NO_DEFAULT_LANGUAGE = 'There must be at least one language.';

    case CLIENT_CANNOT_DELETE_MODEL = 'Cannot delete model';
    case CLIENT_OPTION_NOT_RELATED_TO_ATTRIBUTE = 'Option is not related to provided attribute';

    case CLIENT_SALES_CHANNEL_NOT_FOUND = 'Sales channel not defined or found';

    case CLIENT_CAPTCHA_FAILED = 'Failed captcha verification';

    case SERVER_CDN_ERROR = 'CDN responded with an error';
    case SERVER_ERROR = 'Server responded with an error';
    case SERVER_ORDER_STATUSES_NOT_CONFIGURED = 'Order statuses are not configured';
    case SERVER_TRANSACTION_ERROR = 'Unexpected error occurred during the database transaction.';
    case SERVER_PAYMENT_MICROSERVICE_ERROR = 'Payment service error.';
    case SERVER_SHIPPING_TYPE_NO_VALIDATION = 'Validation is not implemented for selected shipping type';
    case SERVER_NO_PRICE_MATCHING_CRITERIA = 'No price exists matching the given criteria';
    case SERVER_PRICE_UNKNOWN_CURRENCY = 'Found price with unknown currency';

    case SERVER_CAPTCHA_ERROR = 'Failed processing captcha token';

    case ORDER_NOT_ENOUGH_ITEMS_IN_WAREHOUSE = 'Not every item is available';
    case ORDER_SHIPPING_METHOD_TYPE_MISMATCH = 'Selected shipping methods don\'t match selected product types';

    case PRODUCT_IS_NOT_ON_WISHLIST = 'Product is not on wishlist';
    case PRODUCT_SET_IS_NOT_ON_FAVOURITES_LIST = 'Product set is not on favourites list';

    case PRODUCT_PURCHASE_LIMIT = 'The limit of purchased product units per user has been exceeded';

    case PRODUCT_NOT_FOUND = 'Product with given id was not found';

    case PAYMENT_METHOD_NOT_AVAILABLE_FOR_SHIPPING = 'Payment method not available for selected shipping method';

    case CLIENT_ALREADY_HAS_ACCOUNT = 'User with given email already exist.';

    case CLIENT_PROVIDER_MERGE_TOKEN_EXPIRED = 'Provider merge token has expired';
    case CLIENT_PROVIDER_MERGE_TOKEN_INVALID = 'Provider merge token is invalid';
    case CLIENT_PROVIDER_MERGE_TOKEN_MISMATCH = 'Provider merge token is for an account with different email address';
    case PUBLISHING_TRANSLATION_EXCEPTION = "Model doesn't have all required translations to be published";
    case CLIENT_JOINING_NON_JOINABLE_ROLE = 'Can\'t join to a non joinable role';
    case CLIENT_UPDATE_NOT_REGULAR_JOINABLE = 'Can\'t update is_joinable field in role types other than regular';

    case CLIENT_FULL_NAME = 'The name must contains first name and last name';
    case CLIENT_PRODUCT_OPTION = 'The product option is required';
    case CLIENT_SCHEMA_INVALID = 'Selected schemas are invalid';
    case CLIENT_SCHEMA_OPTIONS_INVALID = 'Selected schemas options are invalid';
    case CLIENT_EMAIL_TAKEN = 'The email has already been taken';

    case CLIENT_ORGANIZATION_EXIST = 'Organization with given VAT already exists';
    case CLIENT_ORGANIZATION_VAT_REQUIRED = 'Organization billing_address require VAT number';

    // Aliases
    public const CLIENT_NO_ACCESS_TO_DOWNLOAD_DOCUMENT = self::CLIENT_NO_ACCESS;
    public const CLIENT_PROVIDER_HAS_NO_CONFIG = self::CLIENT_PROVIDER_IS_NOT_ACTIVE;

    public function getCode(): int
    {
        return match ($this) {
            self::CLIENT_DISCOUNT_TYPE_NOT_SUPPORTED,
            self::CLIENT_DELETE_WHEN_RELATION_EXISTS,
            self::CLIENT_MODEL_NOT_AUDITABLE,
            self::CLIENT_APPS_NO_ACCESS,
            self::CLIENT_USERS_NO_ACCESS => 400,
            self::CLIENT_TFA_REQUIRED,
            self::CLIENT_WEBHOOK_APP_ACTION,
            self::CLIENT_WEBHOOK_USER_ACTION => 403,
            self::SERVER_CDN_ERROR,
            self::SERVER_ERROR,
            self::SERVER_ORDER_STATUSES_NOT_CONFIGURED,
            self::SERVER_PAYMENT_MICROSERVICE_ERROR => 500,
            default => 422,
        };
    }
}
