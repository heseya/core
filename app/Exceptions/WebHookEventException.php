<?php

namespace App\Exceptions;

use Throwable;

class WebHookEventException extends StoreException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $previous);
    }
}
