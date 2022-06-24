<?php

namespace App\Exceptions;

use App\Enums\ExceptionsEnums\Exceptions;
use Throwable;

class ServerException extends StoreException
{
    public function __construct(
        Exceptions $enum,
        int $code = 0,
        ?Throwable $previous = null,
        bool $simpleLogs = false,
        array $errorArray = [],
    ) {
        parent::__construct($enum->value, $code, $previous, $simpleLogs, $errorArray);
    }
}
