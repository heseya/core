<?php

namespace App\Exceptions;

use Throwable;

class GoogleProductCategoryFileException extends StoreException
{
    public function __construct(
        string $message = 'Google product category file do not exist',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $previous);
    }
}
