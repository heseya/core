<?php

namespace App\Exceptions;

use Throwable;

class ClientException extends StoreException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        $simpleLogs = false,
        $errorArray = [],
    ) {
        parent::__construct($message, $code, $previous, $simpleLogs, $errorArray);
    }
}
