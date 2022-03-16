<?php

namespace App\Rules;

use App\Enums\ShippingType;
use App\Models\ShippingMethod;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ImplicitRule;

class ShippingAddressRequired implements ImplicitRule, DataAwareRule
{
    protected array $data = [];

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if(!array_key_exists('shipping_method_id', $this->data)) {
            return true;
        }

        $shippingMethod = ShippingMethod::find($this->data['shipping_method_id']);
        if (($shippingMethod->shipping_type === ShippingType::POINT
                || $shippingMethod->shipping_type === ShippingType::ADDRESS)
            && $value === null
        ) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Shipping address is required with this shipping method type.';
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}
