<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum ErrorCode: string
{
    use EnumTrait;

    case BAD_GATEWAY = 'Bad gateway';
    case BAD_REQUEST = 'Bad request';
    case FORBIDDEN = 'Forbidden';
    case INTERNAL_SERVER_ERROR = 'Internal server error';
    case NOT_FOUND = 'Not found';
    case UNAUTHORIZED = 'Unauthorized';
    case UNPROCESSABLE_ENTITY = 'Unprocessable entity';
    case VALIDATION_ERROR = 'Validation error';
    case NOT_ACCEPTABLE = 'Not acceptable';

    public function getCode(): int
    {
        return match ($this) {
            self::BAD_GATEWAY => 502,
            self::BAD_REQUEST => 400,
            self::FORBIDDEN => 403,
            self::NOT_FOUND => 404,
            self::UNAUTHORIZED => 401,
            self::NOT_ACCEPTABLE => 406,
            self::UNPROCESSABLE_ENTITY, self::VALIDATION_ERROR => 422,
            default => 500,
        };
    }
}
