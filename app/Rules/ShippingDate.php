<?php

namespace App\Rules;

use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

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
        return is_null($this->item->unlimited_stock_shipping_date)
            || $this->item->unlimited_stock_shipping_date < Carbon::now()
            || $value < Carbon::now()
            || $this->item->unlimited_stock_shipping_date >= $value;
    }

    public function message(): string
    {
        return 'Shipping date cannot by grater then unlimited stock shipping date.';
    }
}
