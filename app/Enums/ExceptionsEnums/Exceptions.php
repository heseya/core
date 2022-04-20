<?php

namespace App\Enums\ExceptionsEnums;

use BenSampo\Enum\Enum;

final class Exceptions extends Enum
{
    public const CLIENT_INVALID_INSTALLATION_RESPONSE = 'App has invalid installation response';
    public const CLIENT_FAILED_TO_CONNECT_WITH_APP = 'Failed to connect with application';
    public const CLIENT_FAILED_TO_UNINSTALL_APP = 'Failed to uninstall the application';
    public const CLIENT_ASSIGN_INVALID_PERMISSIONS = 'Assigning invalid permissions';
    public const CLIENT_ADD_APP_WITH_PERMISSIONS_USER_DONT_HAVE = 'Can\'t add an app with permissions you don\'t have';
    public const CLIENT_APP_RESPONDED_WITH_INVALID_CODE = 'Application info responded with invalid status code';
    public const CLIENT_APP_RESPONDED_WITH_INVALID_INFO = 'App responded with invalid info';
    public const CLIENT_APP_WANTS_INVALID_INFO = 'App wants invalid permissions';
    public const CLIENT_ADD_APP_WITHOUT_REQUIRED_PERMISSIONS = 'Can\'t add app without all required permissions';
    public const CLIENT_ADD_PERMISSION_AP_DOESNT_WANT = 'Can\'t add any permissions application doesn\'t want';
    #Bad request
    public const CLIENT_MODEL_NOT_AUDITABLE = 'Model not auditable';
    public const CLIENT_INVALID_CREDENTIALS = 'Invalid credentials';
    public const CLIENT_INVALID_TOKEN = 'Invalid token';
    public const CLIENT_INVALID_IDENTITY_TOKEN = 'Invalid identity token';
    public const CLIENT_USER_DOESNT_EXIST = 'User doesn\'t exist';
    public const CLIENT_INVALID_2FA_TYPE = 'Invalid Two-Factor Authentication type';
    public const CLIENT_TOKEN_INVALID_OR_INACTIVE = 'The token is invalid or inactive. Try to reset your password again';
    public const CLIENT_ONLY_USER_CAN_SET_2FA = 'Only users can set up Two-Factor Authentication';
    #Bad request
    public const CLIENT_DISCOUNT_TYPE_NOT_SUPPORTED = 'Discount type is not supported';
    public const CLIENT_CANNOT_APPLY_SELECTED_DISCOUNT_TYPE = 'Cannot apply selected discount type to order';
    public const CLIENT_NOT_ENOUGH_ITEMS = 'There is not enough items';
    public const CLIENT_WRONG_VALUE = 'Wrong value';
    public const CLIENT_ORDER_EDIT_ERROR ='Error while editing order';

    public const CLIENT_CREATE_ROLE_WITHOUT_PERMISSION = 'Cant create a role with permissions you don\'t have';
    public const CLIENT_UPDATE_ROLE_WITHOUT_PERMISSION = 'Cant update a role with permissions you don\'t have';
    public const CLIENT_DELETE_ROLE_WITHOUT_PERMISSION = 'Cant update a role with permissions you don\'t have';
    public const CLIENT_UPDATE_OWNER_PERMISSION = 'Can\'t update owners permissions';
    public const CLIENT_DELETE_BUILT_IN_ROLE = 'Can\'t delete built-in roles';
    public const CLIENT_GIVE_ROLE_THAT_USER_DOESNT_HAVE = 'Can\'t give a role with permissions you don\'t have to the user';
    public const CLIENT_REMOVE_ROLE_THAT_USER_DOESNT_HAVE = 'Can\'t remove a role with permissions you don\'t have from the user';
    public const CLIENT_ONLY_OWNER_GRANTS_OWNER_ROLE = 'Only owner can grant the owner role';
    public const CLIENT_ONLY_OWNER_REMOVES_OWNER_ROLE = 'Only owner can remove the owner role';
    public const CLIENT_ONE_OWNER_REMAINS = 'There must always be at least one Owner left';

    public const CLIENT_DELETE_WHEN_RELATION_EXISTS = 'Element can\'t be deleted, because it has relations';

    public const CLIENT_MODEL_NOT_SORTABLE = 'Model is not sortable';

    public const SERVER_CDN_ERROR = 'CDN responded with an error';

    public const NOT_FOUND = '';
}
