<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class StrongPassword implements Rule
{
    private int $minLength;
    private bool $needsNumber;
    private bool $needsUppercaseLetter;
    private bool $needsSpecialCharacter;

    /**
     * StrongPassword constructor.
     */
    public function __construct(
        int $minLength = 20,
        bool $needsNumber = true,
        bool $needsUppercaseLetter = true,
        bool $needsSpecialCharacter = true
    ) {
        $this->minLength = $minLength;
        $this->needsNumber = $needsNumber;
        $this->needsUppercaseLetter = $needsUppercaseLetter;
        $this->needsSpecialCharacter = $needsSpecialCharacter;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        if (mb_strlen($value) < $this->minLength) {
            return false;
        }

        // do we need at least 1 number ?
        if ($this->needsNumber && !preg_match('/[0-9]{1,}/', $value)) {
            return false;
        }

        // do we need at least 1 uppercase letter ?
        if ($this->needsUppercaseLetter && !preg_match('/[A-Z]{1,}/', $value)) {
            return false;
        }

        if ($this->needsSpecialCharacter && !preg_match('/[!@Â£\$%\^&\*\(\)_\+#\-\/\\\[\]\{\}\.,=~:;]{1,}/u', $value)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The password is not strong enough.';
    }
}
