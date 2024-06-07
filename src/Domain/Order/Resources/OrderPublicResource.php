<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\Resource;
use App\Models\Order;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

/**
 * @property Order $resource
 */
final class OrderPublicResource extends Resource
{
    use MetadataResource;

    /**
     * @return array<string, mixed>
     */
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
            'shipping_method' => OrderShippingMethodResource::make($this->resource->shippingMethod),
            'digital_shipping_method' => OrderShippingMethodResource::make($this->resource->digitalShippingMethod),
            'created_at' => $this->resource->created_at,
            'sales_channel' => OrderSalesChannelResource::make($this->resource->salesChannel),
            'language' => $this->resource->language,
        ], $this->metadataResource('orders.show_metadata_private'));
    }
}
