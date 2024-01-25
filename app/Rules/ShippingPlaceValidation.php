<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\ShippingType;
use App\Exceptions\ServerException;
use App\Models\Address;
use Closure;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

final class ShippingPlaceValidation implements DataAwareRule, ValidationRule
{
    /**
     * All the data under validation.
     *
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * Set the data under validation.
     *
     * @param array<string, mixed> $data
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @throws ServerException
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!array_key_exists('shipping_method_id', $this->data) || $this->data['shipping_method_id'] === null) {
            return;
        }

        /** @var ShippingMethod $shippingMethod */
        $shippingMethod = ShippingMethod::query()->findOr(
            $this->data['shipping_method_id'],
            fn () => $fail(Exceptions::CLIENT_SHIPPING_METHOD_NOT_EXISTS->value),
        );

        match ($shippingMethod->shipping_type) {
            ShippingType::ADDRESS => $this->address($fail),
            ShippingType::POINT => $this->point($value, $fail),
            ShippingType::POINT_EXTERNAL => $this->pointExternal($value, $fail),
            default => throw new ServerException(Exceptions::SERVER_SHIPPING_TYPE_NO_VALIDATION),
        };
    }

    private function point(mixed $value, Closure $fail): void
    {
        if (Address::query()->where('id', $value)->doesntExist()) {
            $fail(Exceptions::CLIENT_SHIPPING_POINT_NOT_EXISTS->value);
        }
    }

    private function pointExternal(mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(Exceptions::CLIENT_SHIPPING_POINT_STRING->value);
        }
    }

    private function address(Closure $fail): void
    {
        if (Validator::make($this->data, [
            'shipping_place' => ['nullable', 'array', new ShippingAddressRequired()],
            'shipping_place.name' => ['string', 'max:255', new FullName()],
            'shipping_place.phone' => ['string', 'max:20'],
            'shipping_place.address' => ['string', 'max:255', new StreetNumber()],
            'shipping_place.zip' => ['string', 'max:16'],
            'shipping_place.city' => ['string', 'max:255'],
            'shipping_place.country' => ['string', 'size:2'],
            'shipping_place.vat' => ['nullable', 'string', 'max:15'],
        ])->fails()) {
            $fail(Exceptions::CLIENT_SHIPPING_ADDRESS_INVALID->value);
        }
    }
}
