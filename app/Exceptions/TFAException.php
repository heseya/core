<?php

namespace App\Exceptions;

use App\Enums\ExceptionsEnums\Exceptions;
use Throwable;

class TFAException extends StoreException
{
    public function __construct(
        Exceptions $enum,
        int $code = 0,
        ?Throwable $previous = null,
        bool $simpleLogs = false,
        protected ?string $type = '',
    ) {
        parent::__construct($enum->value, $code, $previous);
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
