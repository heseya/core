<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Brick\Money\Money;
use Domain\SalesChannel\Resources\SalesChannelResource;
use Domain\ShippingMethod\Models\ShippingMethod;
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
            'prices' => array_map(
                fn (Money $price) => [
                    'net' => $price->getAmount(),
                    'gross' => $price->getAmount(),
                    'currency' => $price->getCurrency()->getCurrencyCode(),
                ],
                $this->resource->prices ?? [],
            ),
            'public' => $this->resource->public,
            'is_block_list_countries' => $this->resource->is_block_list_countries,
            'is_block_list_products' => $this->resource->is_block_list_products,
            'payment_on_delivery' => $this->resource->payment_on_delivery,
            'payment_methods' => PaymentMethodResource::collection($this->resource->paymentMethods),
            'countries' => CountryResource::collection($this->resource->countries),
            'price_ranges' => PriceRangeResource::collection($this->resource->priceRanges->sortBy('start')),
            'shipping_time_min' => $this->resource->shipping_time_min,
            'shipping_time_max' => $this->resource->shipping_time_max,
            'shipping_type' => $this->resource->shipping_type,
            'integration_key' => $this->resource->integration_key,
            'deletable' => $this->resource->deletable,
            'shipping_points' => AddressResource::collection($this->resource->shippingPoints),
            'product_ids' => $this->resource->products->pluck('id'),
            'product_set_ids' => $this->resource->productSets->pluck('id'),
            'sales_channels' => SalesChannelResource::collection($this->resource->salesChannels),
        ], $this->metadataResource('shipping_methods.show_metadata_private'));
    }
}
