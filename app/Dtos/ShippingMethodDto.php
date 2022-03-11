<?php

namespace App\Dtos;

use App\Http\Requests\ShippingMethodStoreRequest;
use App\Http\Requests\ShippingMethodUpdateRequest;
use App\Models\App;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Support\Facades\Auth;

class ShippingMethodDto extends Dto
{
    protected string $name;
    protected bool $public;
    protected bool $black_list;
    protected ?array $payment_methods;
    protected ?array $countries;
    protected ?array $price_ranges;
    protected int|Missing $shipping_time_min;
    protected int|Missing $shipping_time_max;
    protected string|null $shipping_type;
    protected string|null $integration_key;
    protected AddressDto $shipping_points;
    protected string|null $app_id;


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
            shipping_time_min: $request->input('shipping_time_min', new Missing()),
            shipping_time_max: $request->input('shipping_time_max', new Missing()),
            shipping_type: $request->input('shipping_type'),
            integration_key: $request->input('integration_key'),
            app_id: Auth::user() instanceof App ? Auth::id() : null,
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

    public function getShippingTimeMin(): Missing|int
    {
        return $this->shipping_time_min;
    }

    public function getShippingTimeMax(): Missing|int
    {
        return $this->shipping_time_max;
    }

    public function getShippingType(): string
    {
        return $this->shipping_type;
    }

    public function getIntegrationKey(): string
    {
        return $this->integration_key;
    }
}
