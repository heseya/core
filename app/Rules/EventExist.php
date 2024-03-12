<?php

namespace App\Rules;

use App\Enums\EventType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EventExist implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($value as $v) {
            if (EventType::tryFrom($v) === null) {
                $fail("The event {$v} not found.");
            }
        }
    }
}
