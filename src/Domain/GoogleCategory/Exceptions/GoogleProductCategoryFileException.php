<?php

declare(strict_types=1);

namespace Domain\GoogleCategory\Exceptions;

use App\Exceptions\StoreException;
use Throwable;

final class GoogleProductCategoryFileException extends StoreException
{
    public function __construct(
        string $message = 'Google product category file do not exist',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $previous);
    }
}
