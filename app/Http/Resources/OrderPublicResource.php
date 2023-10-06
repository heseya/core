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
            'cart_total_initial' => $this->resource->cart_total_initial->getAmount(),
            'cart_total' => $this->resource->cart_total->getAmount(),
            'shipping_price_initial' => $this->resource->shipping_price_initial->getAmount(),
            'shipping_price' => $this->resource->shipping_price->getAmount(),
            'summary' => $this->resource->summary->getAmount(),
            'currency' => $this->resource->currency,
            'shipping_method' => ShippingMethodResource::make($this->resource->shippingMethod),
            'digital_shipping_method' => ShippingMethodResource::make($this->resource->digitalShippingMethod),
            'created_at' => $this->resource->created_at,
            'sales_channel' => SalesChannelResource::make($this->resource->salesChannel),
        ], $this->metadataResource('orders.show_metadata_private'));
    }
}
