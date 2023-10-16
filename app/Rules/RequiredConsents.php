<?php

namespace App\Rules;

use App\Models\Consent;
use Illuminate\Contracts\Validation\ImplicitRule;

class RequiredConsents implements ImplicitRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $consents = Consent::where('required', true)->get();

        return $consents
            ->every(fn ($consent) => array_key_exists($consent->getKey(), $value ?? [])
                && $value[$consent->getKey()] === true);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'You must accept the required consents.';
    }
}
