<?php

namespace App\Http\Resources;

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
            'payed' => $this->isPayed(),
            'comment' => $this->comment,
            'created_at' => $this->created_at,
            'status' => $this->status ? StatusResource::make($this->status) : null,
            'delivery_address' => $this->deliveryAddress ? AddressResource::make($this->deliveryAddress) : null,
        ];
    }

    public function view(Request $request): array
    {
        return [
            'invoice_address' => AddressResource::make($this->invoiceAddress),
            'shipping_method' => ShippingMethodResource::make($this->shippingMethod),
            'products' => OrderProductResource::collection($this->products),
            'payments' => PaymentResource::collection($this->payments),
            'shipping_number' => $this->shipping_number,
            'payable' => $this->payable,
            'discounts' => DiscountResource::collection($this->discounts),
        ];
    }
}
