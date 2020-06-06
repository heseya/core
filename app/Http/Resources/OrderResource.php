<?php

namespace App\Http\Resources;

use App\Http\Resources\AddressResource;
use App\Http\Resources\OrderItemResource;
use App\Http\Resources\ShippingMethodResource;

class OrderResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function base($request): array
    {
        $this->shippingMethod->price = $this->shipping_price;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'email' => $this->email,
            'comment' => $this->comment,
            'summary' => $this->summary,
            'created_at' => $this->created_at,
            'payment_status' => $this->payment_status,
            'shop_status' => $this->shop_status,
            'delivery_status' => $this->delivery_status,
            'shipping_method' => ShippingMethodResource::make($this->shippingMethod),
            'items' => OrderItemResource::collection($this->items),
            'delivery_address' => AddressResource::make($this->deliveryAddress),
            'invoice_address' => AddressResource::make($this->invoiceAddress),
        ];
    }
}
