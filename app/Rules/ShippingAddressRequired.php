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
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     */
    public function passes(mixed $attribute, mixed $value): bool
    {
        if (!array_key_exists('shipping_method_id', $this->data)) {
            return true;
        }

        /** @var ShippingMethod|null $shippingMethod */
        $shippingMethod = ShippingMethod::query()->find($this->data['shipping_method_id']);

        if (
            ($shippingMethod?->shipping_type === ShippingType::POINT ||
                $shippingMethod?->shipping_type === ShippingType::ADDRESS) && $value === null
        ) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Shipping address is required with this shipping method type.';
    }

    public function setData($data): ShippingAddressRequired
    {
        $this->data = $data;

        return $this;
    }
}
