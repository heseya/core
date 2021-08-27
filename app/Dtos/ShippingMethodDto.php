<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Illuminate\Http\Request;

class ShippingMethodDto extends Dto implements InstantiateFromRequest
{
    public function __construct(
        protected string $name,
        protected bool $public,
        protected bool $blackList,
        protected ?array $paymentMethods,
        protected ?array $countries,
        protected ?array $priceRanges,
    ) {
    }

    public static function instantiateFromRequest(Request $request): self
    {
        return new self(
            $request->input('name'),
            $request->boolean('public'),
            $request->boolean('black_list'),
            $request->input('payment_methods'),
            $request->input('countries'),
            $request->input('price_ranges'),
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
        return $this->blackList;
    }

    public function getPaymentMethods(): ?array
    {
        return $this->paymentMethods;
    }

    public function getCountries(): ?array
    {
        return $this->countries;
    }

    public function getPriceRanges(): ?array
    {
        return $this->priceRanges;
    }
}
