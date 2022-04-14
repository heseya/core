<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ErrorCode extends Enum
{
    public const NOT_FOUND = 'NOT_FOUND';
    public const INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    public const UNAUTHORIZED = 'UNAUTHORIZED';
    public const FORBIDDEN = 'FORBIDDEN';
    public const UNPROCESSABLE_ENTITY = 'UNPROCESSABLE_ENTITY';
    public const BAD_REQUEST = 'BAD_REQUEST';
    public const BAD_GATEWAY = 'BAD_GATEWAY';

    public static function getCode($value): int
    {
        return match ($value) {
            self::NOT_FOUND => 404,
            self::UNAUTHORIZED => 401,
            self::FORBIDDEN => 403,
            self::UNPROCESSABLE_ENTITY => 422,
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
            default => 'Internal server error',
        };
    }
}
