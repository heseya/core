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
            'email' => $this->resource->email,
            'currency' => $this->resource->currency,
            'summary' => $this->resource->summary,
            'summary_paid' => $this->resource->paid_amount,
            'shipping_price_initial' => $this->resource->shipping_price_initial,
            'shipping_price' => $this->resource->shipping_price,
            'comment' => $this->resource->comment,
            'status' => $this->resource->status ? StatusResource::make($this->resource->status) : null,
            'shipping_method' => $this->resource->shippingMethod ?
                ShippingMethodResource::make($this->resource->shippingMethod) : null,
            'documents' => OrderDocumentResource::collection($this->documents->pluck('pivot')),
            'paid' => $this->resource->paid,
            'cart_total' => $this->resource->cart_total,
            'cart_total_initial' => $this->resource->cart_total_initial,
            'delivery_address' => $this->resource->deliveryAddress ?
                AddressResource::make($this->resource->deliveryAddress) : null,
            'created_at' => $this->resource->created_at,
        ], $this->metadataResource('orders.show_metadata_private'));
    }

    public function view(Request $request): array
    {
        return [
            'invoice_address' => AddressResource::make($this->resource->invoiceAddress),
            'products' => OrderProductResource::collection($this->resource->products),
            'payments' => PaymentResource::collection($this->resource->payments),
            'shipping_number' => $this->resource->shipping_number,
            'discounts' => OrderDiscountResource::collection($this->resource->discounts),
            'buyer' => $this->resource->buyer instanceof User
                ? UserResource::make($this->resource->buyer)->baseOnly() : AppResource::make($this->resource->buyer),
        ];
    }

    public function index(Request $request): array
    {
        return [
            'payable' => $this->resource->payable,
        ];
    }
}
