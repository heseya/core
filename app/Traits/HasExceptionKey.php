<?php

namespace App\Traits;

use App\Enums\ExceptionsEnums\Exceptions;

trait HasExceptionKey
{
    public function getKey(): string
    {
        // @phpstan-ignore-next-line
        return Exceptions::fromValue($this->getMessage())->key;
    }
}
