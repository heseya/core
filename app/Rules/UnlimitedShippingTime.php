<?php

namespace App\Rules;

use App\Models\Item;
use Illuminate\Contracts\Validation\Rule;

class UnlimitedShippingTime implements Rule
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
        return $this->item->unlimited_stock_shipping_time >= $value;
    }

    public function message(): string
    {
        return 'Unlimited stock shipping time cannot by lesser then shipping time.';
    }
}
