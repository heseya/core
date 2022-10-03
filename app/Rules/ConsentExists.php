<?php

namespace App\Rules;

use App\Models\Consent;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class ConsentExists implements Rule
{
    private string $id;

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     */
    public function passes($attribute, $value): bool
    {
        $this->id = Str::after($attribute, 'consents.');
        return Consent::where('id', $this->id)->exists();
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Consent with ID: ' . $this->id . ' does not exist';
    }
}
