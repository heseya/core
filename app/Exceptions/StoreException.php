<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

class StoreException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        protected bool $simpleLogs = false,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function isSimpleLogs(): bool
    {
        return $this->simpleLogs;
    }

    public function logException(): void
    {
        Log::error(
            $this::class
            . '(code: ' . $this->getCode()
            . '): ' . $this->getMessage()
            . ' at ' . $this->getFile()
            . ':(' . $this->getLine() . ')'
        );
    }
}
