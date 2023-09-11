<?php

namespace App\Rules;

use App\Enums\ShippingType;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ImplicitRule;

class ShippingAddressRequired implements DataAwareRule, ImplicitRule
{
    protected array $data = [];

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     */
    public function passes(mixed $attribute, mixed $value): bool
    {
        if (!array_key_exists('shipping_method_id', $this->data)) {
            return true;
        }

        /** @var ShippingMethod|null $shippingMethod */
        $shippingMethod = ShippingMethod::query()->find($this->data['shipping_method_id']);

        return !(
            ($shippingMethod?->shipping_type === ShippingType::POINT
                || $shippingMethod?->shipping_type === ShippingType::ADDRESS) && $value === null
        );
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Shipping address is required with this shipping method type.';
    }

    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }
}
