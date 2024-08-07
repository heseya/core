<?php

namespace App\Exceptions;

use Throwable;

class ServerException extends StoreException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        bool $simpleLogs = false,
        array $errorArray = [],
    ) {
        parent::__construct($message, $code, $previous, $simpleLogs, $errorArray);
    }
}
