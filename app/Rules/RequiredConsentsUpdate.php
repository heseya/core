<?php

namespace App\Rules;

class RequiredConsentsUpdate extends RequiredConsents
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
        if ($value === null) {
            return true;
        }

        return parent::passes($attribute, $value);
    }
}
