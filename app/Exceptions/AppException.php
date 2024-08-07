<?php

namespace App\Exceptions;

use Throwable;

class AppException extends StoreException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        private array $errorArray = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function errors(): array
    {
        return $this->errorArray;
    }
}
