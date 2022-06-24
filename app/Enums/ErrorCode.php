<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum ErrorCode: string
{
    use EnumUtilities;

    case NOT_FOUND = 'Not found';
    case INTERNAL_SERVER_ERROR = 'Internal server error';
    case UNAUTHORIZED = 'Unauthorized';
    case FORBIDDEN = 'Forbidden';
    case UNPROCESSABLE_ENTITY = 'Unprocessable entity';
    case BAD_REQUEST = 'Bad request';
    case BAD_GATEWAY = 'Bad gateway';
    case VALIDATION_ERROR = 'Validation error';

    public static function getCode(ErrorCode $value): int
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
}
