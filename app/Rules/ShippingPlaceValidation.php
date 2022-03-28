<?php

namespace App\Rules;

use App\Enums\ShippingType;
use App\Models\Address;
use App\Models\ShippingMethod;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Support\Facades\Validator;

class ShippingPlaceValidation implements ImplicitRule, DataAwareRule
{
    protected array $data = [];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (!array_key_exists('shipping_method_id', $this->data)) {
            return true;
        }

        $shippingMethod = ShippingMethod::find($this->data['shipping_method_id']);

        return match ($shippingMethod->shipping_type) {
            ShippingType::POINT => Address::where('id', $value)->exists(),
            ShippingType::ADDRESS => !Validator::make($this->data, [
                'shipping_place' => ['nullable', 'array', new ShippingAddressRequired()],
                'shipping_place.name' => ['required_with_all:shipping_place', 'string', 'max:255'],
                'shipping_place.phone' => ['required_with_all:shipping_place', 'string', 'max:20'],
                'shipping_place.address' => ['required_with_all:shipping_place', 'string', 'max:255'],
                'shipping_place.zip' => ['required_with_all:shipping_place', 'string', 'max:16'],
                'shipping_place.city' => ['required_with_all:shipping_place', 'string', 'max:255'],
                'shipping_place.country' => ['required_with_all:shipping_place', 'string', 'size:2'],
                'shipping_place.vat' => ['nullable', 'string', 'max:15'],
            ])->fails(),
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

    public function setData($data): void
    {
        $this->data = $data;
    }
}
