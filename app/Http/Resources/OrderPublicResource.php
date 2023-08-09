<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Domain\Order\Resources\OrderStatusResource;
use Illuminate\Http\Request;

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
            'cart_total_initial' => $this->resource->cart_total_initial,
            'cart_total' => $this->resource->cart_total,
            'shipping_price_initial' => $this->resource->shipping_price_initial,
            'shipping_price' => $this->resource->shipping_price,
            'summary' => $this->resource->summary,
            'currency' => $this->resource->currency,
            'shipping_method' => ShippingMethodResource::make($this->resource->shippingMethod),
            'digital_shipping_method' => ShippingMethodResource::make($this->resource->digitalShippingMethod),
            'created_at' => $this->resource->created_at,
        ], $this->metadataResource('orders.show_metadata_private'));
    }
}
