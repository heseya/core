<?php

namespace App\Exceptions;

use App\Enums\ExceptionsEnums\Exceptions;
use Throwable;

class ServerException extends StoreException
{
    public function __construct(
        Exceptions|string $message = '',
        ?Throwable $previous = null,
        bool $simpleLogs = false,
        array $errorArray = [],
    ) {
        parent::__construct($message, $previous, $simpleLogs, $errorArray);
    }
}
