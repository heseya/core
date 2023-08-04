<?php

namespace App\Exceptions;

use Throwable;

class AppException extends StoreException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
        private array $errorArray = [],
    ) {
        parent::__construct($message, $previous);
    }

    public function errors(): array
    {
        return $this->errorArray;
    }
}
