<?php

namespace App\Exceptions;

use App\Enums\ExceptionsEnums\Exceptions;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

class StoreException extends Exception
{
    protected Exceptions|string $exception;

    public function __construct(
        Exceptions|string $exception,
        ?Throwable $previous = null,
        protected bool $simpleLogs = false,
        private readonly array $errorArray = [],
    ) {
        $this->exception = is_string($exception)
            ? Exceptions::coerce($exception) ?? $exception
            : $exception;

        parent::__construct(
            $this->exception instanceof Exceptions ? $this->exception->value : $this->exception,
            $this->exception instanceof Exceptions ? $this->exception->getCode() : 422,
            $previous,
        );
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
                . ':(' . $this->getLine() . ')',
        );
    }

    public function errors(): array
    {
        return $this->errorArray;
    }

    public function getKey(): string
    {
        return $this->exception instanceof Exceptions
            ? $this->exception->name
            : '';
    }
}
