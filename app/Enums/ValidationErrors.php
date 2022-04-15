<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ValidationErrors extends Enum
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
}
