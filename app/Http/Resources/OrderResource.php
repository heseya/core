<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class OrderResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'code' => $this->resource->code,
            'currency' => $this->resource->currency,
            'email' => $this->resource->email,
            'summary' => $this->resource->summary,
            'summary_paid' => $this->resource->paid_amount,
            'shipping_price' => $this->resource->shipping_price,
            'paid' => $this->resource->paid,
            'comment' => $this->resource->comment,
            'created_at' => $this->resource->created_at,
            'status' => $this->resource->status ? StatusResource::make($this->resource->status) : null,
            'delivery_address' => $this->resource->deliveryAddress ?
                AddressResource::make($this->resource->deliveryAddress) : null,
            'shipping_method' => $this->resource->shippingMethod ?
                ShippingMethodResource::make($this->resource->shippingMethod) : null,
        ], $this->metadataResource('orders.show_metadata_private'));
    }

    public function view(Request $request): array
    {
        return [
            'invoice_address' => AddressResource::make($this->resource->invoiceAddress),
            'shipping_method' => ShippingMethodResource::make($this->resource->shippingMethod),
            'products' => OrderProductResource::collection($this->resource->products),
            'payments' => PaymentResource::collection($this->resource->payments),
            'shipping_number' => $this->resource->shipping_number,
            'payable' => $this->resource->payable,
            'discounts' => DiscountResource::collection($this->resource->discounts),
            'user' => $this->resource->user instanceof User ?
                UserResource::make($this->resource->user)->baseOnly() :
                AppResource::make($this->resource->user),
        ];
    }
}
