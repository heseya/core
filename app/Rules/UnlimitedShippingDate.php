<?php

namespace App\Rules;

use App\Models\Item;
use Illuminate\Contracts\Validation\Rule;

class UnlimitedShippingDate implements Rule
{
    public function __construct(private Item $item)
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        return $this->item->unlimited_stock_shipping_date >= $value;
    }

    public function message(): string
    {
        return 'Unlimited stock shipping date cannot by lesser then shipping date.';
    }
}
