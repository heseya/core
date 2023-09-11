<?php

namespace App\Exceptions;

use App\Enums\ExceptionsEnums\Exceptions;
use Throwable;

class PublishingException extends StoreException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }

    public function getKey(): string
    {
        return Exceptions::PUBLISHING_TRANSLATION_EXCEPTION->name;
    }
}
