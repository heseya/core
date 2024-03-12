<?php

namespace App\Exceptions;

use Throwable;

class TFAException extends StoreException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
        bool $simpleLogs = false,
        protected ?string $type = '',
    ) {
        parent::__construct($message, $previous);
        $this->simpleLogs = $simpleLogs;
    }

    public function isTypeSet(): bool
    {
        return $this->type !== '';
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'type' => $this->type,
        ];
    }
}
