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
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->id = Str::after($attribute, 'consents.');

        return Consent::where('id', $this->id)->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Consent with ID: ' . $this->id . ' does not exist';
    }
}
