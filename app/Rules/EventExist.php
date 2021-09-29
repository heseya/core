<?php

namespace App\Rules;

use App\Enums\EventPermissionType;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class EventExist implements Rule
{
    private $event;

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value)
    {
        foreach ($value as $v) {
            if (!EventPermissionType::hasKey(Str::upper(Str::snake($v)))) {
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
