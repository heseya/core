<?php

namespace App\Exceptions;

use Throwable;

class AuthException extends StoreException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        bool $simpleLogs = false,
    ) {
        parent::__construct($message, $code, $previous, $simpleLogs);
    }
}
