<?php

namespace App\Exceptions;

use Throwable;

class AuthException extends StoreException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
        bool $simpleLogs = false,
    ) {
        parent::__construct($message, $previous, $simpleLogs);
    }
}
