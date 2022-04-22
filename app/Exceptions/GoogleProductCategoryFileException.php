<?php

namespace App\Exceptions;

use Throwable;

class GoogleProductCategoryFileException extends StoreException
{
    public function __construct(
        string $message = 'Google product category file do not exist',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
