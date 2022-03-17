<?php

namespace App\Http\Resources;

use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Request;

class OrderResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'code' => $this->code,
            'email' => $this->email,
            'currency' => $this->currency,
            'summary' => $this->summary,
            'summary_paid' => $this->paid_amount,
            'shipping_price' => $this->shipping_price,
            'paid' => $this->paid,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
            'status' => $this->status ? StatusResource::make($this->status) : null,
            'shipping_address' => $this->shippingAddress ? AddressResource::make($this->shippingAddress) : null,
            'shipping_method' => $this->shippingMethod ? ShippingMethodResource::make($this->shippingMethod) : null,
            'invoice_requested' => $this->invoice_requested,
            'shipping_place' => AddressResource::make(Address::find($this->shipping_place)) ?? $this->shipping_place,
        ];
    }

    public function view(Request $request): array
    {
        return [
            'billing_address' => AddressResource::make($this->invoiceAddress),
            'shipping_method' => ShippingMethodResource::make($this->shippingMethod),
            'products' => OrderProductResource::collection($this->products),
            'payments' => PaymentResource::collection($this->payments),
            'shipping_number' => $this->shipping_number,
            'payable' => $this->payable,
            'discounts' => DiscountResource::collection($this->discounts),
            'user' => $this->user instanceof User
                ? UserResource::make($this->user)->baseOnly() : AppResource::make($this->user),
        ];
    }
}
