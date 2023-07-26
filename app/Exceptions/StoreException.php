<?php

namespace App\Exceptions;

use App\Enums\ExceptionsEnums\Exceptions;
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
        private readonly array $errorArray = [],
    ) {
        parent::__construct($message, $code, $previous);
        $this->code = Exceptions::getCode($message);
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

    public function errors(): array
    {
        return $this->errorArray;
    }

    public function getKey(): string
    {
        return Exceptions::fromValue($this->getMessage())->key ?? '';
    }
}
