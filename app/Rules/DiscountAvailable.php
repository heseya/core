<?php

namespace App\Rules;

use App\Models\Discount;
use Illuminate\Contracts\Validation\Rule;

class DiscountAvailable implements Rule
{
    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        $discount = Discount::where('code', $value)->firstOrFail();

        return $discount->available;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Discount code ":value" is not available';
    }
}
