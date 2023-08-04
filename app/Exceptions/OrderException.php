<?php

namespace App\Exceptions;

use App\Enums\ExceptionsEnums\Exceptions;
use Throwable;

class OrderException extends StoreException
{
    public function __construct(
        Exceptions|string $message = '',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }
}
