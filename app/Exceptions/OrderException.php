<?php

namespace App\Exceptions;

use Throwable;

class OrderException extends StoreException
{
    public function __construct(string $message = '', int $code = 422, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
