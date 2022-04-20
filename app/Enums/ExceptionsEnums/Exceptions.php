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
}
