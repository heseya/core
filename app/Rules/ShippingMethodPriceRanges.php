<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ShippingMethodPriceRanges implements Rule
{
    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        // for update
        if ($value === null || !$value) {
            return true;
        }

        foreach ($value as $item) {
            if (!isset($item['start'])) {
                return false;
            }

            $minimumValue = (float) $item['start'];

            if ($minimumValue === 0.0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'No element of the price range begins with 0';
    }
}
