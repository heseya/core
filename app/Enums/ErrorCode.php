<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ErrorCode extends Enum
{
    public const NOT_FOUND = 'Not found';
    public const INTERNAL_SERVER_ERROR = 'Internal server error';
    public const UNAUTHORIZED = 'Unauthorized';
    public const FORBIDDEN = 'Forbidden';
    public const UNPROCESSABLE_ENTITY = 'Unprocessable entity';
    public const BAD_REQUEST = 'Bad request';
    public const BAD_GATEWAY = 'Bad gateway';
    public const VALIDATION_ERROR = 'Validation error';

    public static function getCode($value): int
    {
        return match ($value) {
            self::NOT_FOUND => 404,
            self::UNAUTHORIZED => 401,
            self::FORBIDDEN => 403,
            self::UNPROCESSABLE_ENTITY, self::VALIDATION_ERROR => 422,
            self::BAD_REQUEST => 400,
            self::BAD_GATEWAY => 502,
            default => 500,
        };
    }

    public static function getMessage($value): string
    {
        return match ($value) {
            self::NOT_FOUND => 'Not found',
            self::UNAUTHORIZED => 'Unauthorized',
            self::FORBIDDEN => 'Forbidden',
            self::UNPROCESSABLE_ENTITY => 'Unprocessable entity',
            self::BAD_REQUEST => 'Bad request',
            self::BAD_GATEWAY => 'Bad gateway',
            self::VALIDATION_ERROR => 'Validation error',
            default => 'Internal server error',
        };
    }
}
