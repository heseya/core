<?php

namespace Heseya\Dto;

use Exception;
use Throwable;

class DtoException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
