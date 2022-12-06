<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\ShippingMethodStoreRequest;
use App\Http\Requests\ShippingMethodUpdateRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class ShippingMethodDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    protected string|Missing $name;
    protected bool|Missing $public;
    protected bool|Missing $block_list;
    protected ?array $payment_methods;
    protected ?array $countries;
    protected ?array $price_ranges;
    protected int|Missing $shipping_time_min;
    protected int|Missing $shipping_time_max;

    protected array|Missing $metadata;

    public static function instantiateFromRequest(
        FormRequest|ShippingMethodStoreRequest|ShippingMethodUpdateRequest $request,
    ): self {
        return new self(
            name: $request->input('name', new Missing()),
            public: $request->input('public', new Missing()),
            block_list: $request->input('block_list', new Missing()),
            payment_methods: $request->input('payment_methods'),
            countries: $request->input('countries'),
            price_ranges: $request->input('price_ranges'),
            shipping_time_min: $request->input('shipping_time_min', new Missing()),
            shipping_time_max: $request->input('shipping_time_max', new Missing()),
            metadata: self::mapMetadata($request),
        );
    }

    public function getName(): string|Missing
    {
        return $this->name;
    }

    public function isPublic(): bool|Missing
    {
        return $this->public;
    }

    public function isBlockList(): bool|Missing
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
}
