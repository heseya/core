<?php

namespace App\Rules;

class RequiredConsentsUpdate extends RequiredConsents
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     */
    public function passes($attribute, $value): bool
    {
        if ($value === null) {
            return true;
        }

        return parent::passes($attribute, $value);
    }
}
