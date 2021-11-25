<?php

namespace App\Dtos;

use App\Http\Requests\ShippingMethodStoreRequest;
use App\Http\Requests\ShippingMethodUpdateRequest;
use Heseya\Dto\Dto;

class ShippingMethodDto extends Dto
{
    protected string $name;
    protected bool $public;
    protected bool $black_list;
    protected ?array $payment_methods;
    protected ?array $countries;
    protected ?array $price_ranges;

    public static function instantiateFromRequest(
        ShippingMethodStoreRequest|ShippingMethodUpdateRequest $request,
    ): self {
        return new self(
            name: $request->input('name'),
            public: $request->boolean('public'),
            black_list: $request->boolean('black_list'),
            payment_methods: $request->input('payment_methods'),
            countries: $request->input('countries'),
            price_ranges: $request->input('price_ranges'),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function isBlackList(): bool
    {
        return $this->black_list;
    }

    public function getPaymentMethods(): ?array
    {
        return $this->payment_methods;
    }

    public function getCountries(): ?array
    {
        return $this->countries;
    }

    public function getPriceRanges(): ?array
    {
        return $this->price_ranges;
    }
}
