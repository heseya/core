<?php

namespace App\Rules;

use App\Enums\ShippingType;
use App\Models\ShippingMethod;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ImplicitRule;

class ShippingPlaceValidation implements ImplicitRule, DataAwareRule
{
    protected array $data = [];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (!array_key_exists('shipping_method_id', $this->data)) {
            return true;
        }
        $shippingMethod = ShippingMethod::find($this->data['shipping_method_id']);
        return match ($shippingMethod->shipping_type) {
            ShippingType::POINT, ShippingType::ADDRESS => array_key_exists('shipping_address', $this->data),
            ShippingType::POINT_EXTERNAL => is_string($value),
            default => true,
        };
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'Shipping place data is incorrect.';
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}
