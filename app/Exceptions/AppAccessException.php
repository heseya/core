<?php

namespace App\Exceptions;

use Throwable;

class AppAccessException extends StoreException
{
    public function __construct(
        string $message = 'Applications cannot access this endpoint',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
