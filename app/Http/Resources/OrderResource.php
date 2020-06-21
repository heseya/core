<?php

namespace App\Http\Resources;

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
            'summary' => $this->summary,
            'created_at' => $this->created_at,
            'status' => StatusResource::make($this->status),
            'shipping_method' => ShippingMethodResource::make($this->shippingMethod),
            'delivery_address' => AddressResource::make($this->deliveryAddress),
        ];
    }

    public function view($request): array
    {
        return [
            'invoice_address' => AddressResource::make($this->invoiceAddress),
            'comment' => $this->comment,
            'items' => OrderItemResource::collection($this->items),
        ];
    }
}
