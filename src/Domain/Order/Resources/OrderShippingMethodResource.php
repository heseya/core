<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\Resource;
use App\Traits\MetadataResource;
use Brick\Money\Money;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Http\Request;

/**
 * @property ShippingMethod $resource
 */
final class OrderShippingMethodResource extends Resource
{
    use MetadataResource;

    /**
     * @return array<string, mixed>
     */
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
            'payment_methods' => [],
            'countries' => [],
            'price_ranges' => [],
            'shipping_time_min' => $this->resource->shipping_time_min,
            'shipping_time_max' => $this->resource->shipping_time_max,
            'shipping_type' => $this->resource->shipping_type,
            'integration_key' => $this->resource->integration_key,
            'deletable' => $this->resource->deletable,
            'deleted_at' => $this->resource->deleted_at,
            'shipping_points' => [],
            'product_ids' => [],
            'product_set_ids' => [],
            'sales_channels' => [],
        ], $this->metadataResource('shipping_methods.show_metadata_private'));
    }
}
