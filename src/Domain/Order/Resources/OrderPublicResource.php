<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\Resource;
use App\Models\Order;
use App\Traits\MetadataResource;
use Domain\Order\Dtos\OrderPriceDto;
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
            'cart_total_initial' => OrderPriceDto::from($this->resource->cart_total_initial, $this->resource->vat_rate, false),
            'cart_total' => OrderPriceDto::from($this->resource->cart_total, $this->resource->vat_rate, false),
            'shipping_price_initial' => OrderPriceDto::from($this->resource->shipping_price_initial, $this->resource->vat_rate, false),
            'shipping_price' => OrderPriceDto::from($this->resource->shipping_price, $this->resource->vat_rate, false),
            'summary' => OrderPriceDto::from($this->resource->summary, $this->resource->vat_rate, false),
            'currency' => $this->resource->currency,
            'shipping_method' => OrderShippingMethodResource::make($this->resource->shippingMethod),
            'digital_shipping_method' => OrderShippingMethodResource::make($this->resource->digitalShippingMethod),
            'created_at' => $this->resource->created_at,
            'sales_channel' => OrderSalesChannelResource::make($this->resource->salesChannel),
            'language' => $this->resource->language,
            'payment_method_type' => $this->resource->payment_method_type,
        ], $this->metadataResource('orders.show_metadata_private'));
    }
}
