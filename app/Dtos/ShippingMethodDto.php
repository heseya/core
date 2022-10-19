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
    protected bool $block_list;
    protected ?array $payment_methods;
    protected ?array $countries;
    protected ?array $price_ranges;
    protected int|Missing $shipping_time_min;
    protected int|Missing $shipping_time_max;
    protected string|Missing $shipping_type;
    protected string|null $integration_key;
    protected ?array $shipping_points;
    protected string|null $app_id;

    public static function instantiateFromRequest(
        ShippingMethodStoreRequest|ShippingMethodUpdateRequest $request,
    ): self {
        return new self(
            name: $request->input('name'),
            public: $request->boolean('public'),
            block_list: $request->boolean('block_list'),
            payment_methods: $request->input('payment_methods'),
            countries: $request->input('countries'),
            price_ranges: $request->input('price_ranges'),
            shipping_time_min: $request->input('shipping_time_min', new Missing()),
            shipping_time_max: $request->input('shipping_time_max', new Missing()),
            shipping_type: $request->input('shipping_type', 'none'),
            shipping_points: $request->input('shipping_points'),
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

    public function isBlockList(): bool
    {
        return $this->block_list;
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

    public function getShippingPoints(): ?array
    {
        return $this->shipping_points;
    }

    public function getIntegrationKey(): string
    {
        return $this->integration_key;
    }

    public function getAppId(): string|null
    {
        return $this->app_id;
    }
}
