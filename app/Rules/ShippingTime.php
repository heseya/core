<?php

namespace App\Rules;

use App\Models\Item;
use Illuminate\Contracts\Validation\Rule;

class ShippingTime implements Rule
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
        return is_null($this->item->unlimited_stock_shipping_time)
            || $this->item->unlimited_stock_shipping_time >= $value;
    }

    public function message(): string
    {
        return 'Shipping time cannot by grater then unlimited stock shipping time';
    }
}
