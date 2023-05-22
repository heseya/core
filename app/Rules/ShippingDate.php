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

        return is_null($this->item->unlimited_stock_shipping_date)
            || $this->item->unlimited_stock_shipping_date < Carbon::now()
            || $time < Carbon::now()
            || $this->item->unlimited_stock_shipping_date >= $time;
    }

    public function message(): string
    {
        return 'Shipping date cannot by grater then unlimited stock shipping date.';
    }
}
