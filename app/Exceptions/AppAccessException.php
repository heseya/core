<?php

namespace App\Exceptions;

use Throwable;

class AppAccessException extends StoreException
{
    public function __construct(
        string $message = 'Applications cannot access this endpoint',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $previous);
    }
}
