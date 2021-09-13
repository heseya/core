<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ProhibitedWith implements Rule
{
    private string $field;

    public function __construct($field)
    {
        $this->field = $field;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        return !request()->has($this->field);
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute field is prohibited when ' . $this->field . ' field is present';
    }
}
