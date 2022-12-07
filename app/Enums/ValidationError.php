<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ValidationError extends Enum
{
    public const REQUIRED = 'VALIDATION_REQUIRED';
    public const STRING = 'VALIDATION_STRING';
    public const NUMERIC = 'VALIDATION_NUMERIC';
    public const ARRAY = 'VALIDATION_ARRAY';
    public const MIN = 'VALIDATION_MIN';
    public const MAX = 'VALIDATION_MAX';
    public const BETWEEN = 'VALIDATION_BETWEEN';
    public const DIGITS = 'VALIDATION_DIGITS';
    public const ALPHA = 'VALIDATION_ALPHA';
    public const EMAIL = 'VALIDATION_EMAIL';
    public const EXISTS = 'VALIDATION_EXISTS';
    public const FILE = 'VALIDATION_FILE';
    public const REGEX = 'VALIDATION_REGEX';
    public const SIZE = 'VALIDATION_SIZE';
    public const UNIQUE = 'VALIDATION_UNIQUE';
    public const URL = 'VALIDATION_URL';
    public const UUID = 'VALIDATION_UUID';
    public const PASSWORD = 'VALIDATION_PASSWORD';
    public const PASSWORDLENGTH = 'VALIDATION_PASSWORD_LENGTH';
    public const PASSWORDCOMPROMISED = 'VALIDATION_PASSWORD_COMPROMISED';
    public const DATE = 'VALIDATION_DATE';
    public const DISTINCT = 'VALIDATION_DISTINCT';
    public const IN = 'VALIDATION_IN';
    public const PRESENT = 'VALIDATION_PRESENT';
    public const INTEGER = 'VALIDATION_INTEGER';
    public const FILLED = 'VALIDATION_FILLED';
    public const ALPHADASH = 'VALIDATION_ALPHA_DASH';
    public const MIMETYPES = 'VALIDATION_MIMETYPES';
    public const BEFOREOREQUAL = 'VALIDATION_BEFORE_OR_EQUAL';
    public const REQUIREDWITH = 'VALIDATION_REQUIRED_WITH';
    public const UNIQUEIDINREQUEST = 'VALIDATION_UNIQUE_ID_IN_REQUEST';
    public const APPUNIQUEURL = 'VALIDATION_APP_UNIQUE_ID';
    public const ATTRIBUTEOPTIONEXIST = 'VALIDATION_ATTRIBUTE_OPTION_EXISTS';
    public const BOOLEAN = 'VALIDATION_BOOLEAN';
    public const CANSHOWPRIVATEMETADATA = 'VALIDATION_CAN_SHOW_PRIVATE_METADATA';
    public const CONSENTEXISTS = 'VALIDATION_CONSENT_EXISTS';
    public const DECIMAL = 'VALIDATION_DECIMAL';
    public const ENUMKEY = 'VALIDATION_ENUM_KEY';
    public const EVENTEXIST = 'VALIDATION_EVENT_EXISTS';
    public const OPTIONAVAILABLE = 'VALIDATION_OPTION_AVAILABLE';
    public const PRODUCTATTRIBUTEOPTIONS = 'VALIDATION_PRODUCT_ATTRIBUTE_OPTIONS';
    public const PRODUCTPUBLIC = 'VALIDATION_PRODUCT_PUBLIC';
    public const PROHIBITEDUNLESS = 'VALIDATION_PROHIBITED_UNLESS';
    public const PROHIBITEDWITH = 'VALIDATION_PROHIBITED_WITH';
    public const REQUIREDCONSENTS = 'VALIDATION_REQUIRED_CONSENTS';
    public const SHIPPINGMETHODPRICERANGES = 'VALIDATION_SHIPPING_METHOD_PRICE_RANGES';
    public const AFTEROREQUAL = 'VALIDATION_AFTER_OR_EQUAL';
    public const ENUMVALUE = 'VALIDATION_ENUM_VALUE';
    public const REQUIREDWITHALL = 'VALIDATION_REQUIRED_WITH_ALL';
    public const GTE = 'VALIDATION_GTE';
    public const REQUIREDCONSENTSUPDATE = 'VALIDATION_REQUIRED_CONSENTS';
    public const MEDIASLUG = 'VALIDATION_MEDIA_SLUG';
    public const PHONE = 'VALIDATION_PHONE';
    public const AUTHPROVIDERACTIVE = 'VALIDATION_AUTH_PROVIDER_ACTIVE';
}
