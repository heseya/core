<?php

namespace App\Rules;

use App\Models\Item;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class ShippingDate implements Rule
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
        $time = Carbon::parse($value);

        return $this->item->unlimited_stock_shipping_date === null
            || $this->item->unlimited_stock_shipping_date->isPast()
            || $time->isPast()
            || !$time->isAfter($this->item->unlimited_stock_shipping_date);
    }

    public function message(): string
    {
        return 'Shipping date cannot by grater then unlimited stock shipping date.';
    }
}
