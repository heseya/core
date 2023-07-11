<?php

namespace App\Http\Resources;

use App\Models\Price;
use App\Models\ShippingMethod;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

/**
 * @property ShippingMethod $resource
 */
class ShippingMethodResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'price' => $this->resource->price ? ['gross' => $this->resource->price->getAmount()] : null,
            'public' => $this->resource->public,
            'block_list' => $this->resource->block_list,
            'payment_methods' => PaymentMethodResource::collection($this->resource->paymentMethods),
            'countries' => CountryResource::collection($this->resource->countries),
            'price_ranges' => PriceRangeResource::collection($this->resource->priceRanges->sortBy('start')),
            'shipping_time_min' => $this->resource->shipping_time_min,
            'shipping_time_max' => $this->resource->shipping_time_max,
            'shipping_type' => $this->resource->shipping_type,
            'integration_key' => $this->resource->integration_key,
            'deletable' => $this->resource->deletable,
            'shipping_points' => AddressResource::collection($this->resource->shippingPoints),
        ], $this->metadataResource('shipping_methods.show_metadata_private'));
    }
}
