<?php

namespace App\Rules;

use App\Enums\EventType;
use Illuminate\Contracts\Validation\Rule;

class EventExist implements Rule
{
    private $event;

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value)
    {
        foreach ($value as $v) {
            if (!EventType::hasValue($v)) {
                $this->event = $v;
                return false;
            }
        }
        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message()
    {
        return 'The event ' . $this->event . ' not found.';
    }
}