<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Traits\MetadataResource;
use Domain\Order\Resources\OrderStatusResource;
use Domain\SalesChannel\Resources\SalesChannelResource;
use Illuminate\Http\Request;

/**
 * @property Order $resource
 */
class OrderPublicResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'code' => $this->resource->code,
            'status' => OrderStatusResource::make($this->resource->status),
            'paid' => $this->resource->paid,
            'payable' => $this->resource->payable,
            'cart_total_initial' => PriceResource::make($this->resource->cart_total_initial),
            'cart_total' => PriceResource::make($this->resource->cart_total),
            'shipping_price_initial' => PriceResource::make($this->resource->shipping_price_initial),
            'shipping_price' => PriceResource::make($this->resource->shipping_price),
            'summary' => PriceResource::make($this->resource->summary),
            'currency' => $this->resource->currency,
            'shipping_method' => ShippingMethodResource::make($this->resource->shippingMethod),
            'digital_shipping_method' => ShippingMethodResource::make($this->resource->digitalShippingMethod),
            'created_at' => $this->resource->created_at,
            'sales_channel' => SalesChannelResource::make($this->resource->salesChannel),
        ], $this->metadataResource('orders.show_metadata_private'));
    }
}
