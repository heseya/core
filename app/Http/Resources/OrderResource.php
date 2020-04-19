<?php

namespace App\Http\Resources;

use App\Http\Resources\AddressResource;
use App\Http\Resources\OrderItemResource;
use App\Http\Resources\ShippingMethodResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->shippingMethod->price = $this->shipping_price;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'email' => $this->email,
            'comment' => $this->comment,
            'summary' => $this->summary,
            'payment_status' => $this->payment_status,
            'shop_status' => $this->shop_status,
            'delivery_status' => $this->delivery_status,
            'shipping_method' => new ShippingMethodResource($this->shippingMethod),
            'items' => OrderItemResource::collection($this->items),
            'delivery_address' => new AddressResource($this->deliveryAddress),
            'invoice_address' => new AddressResource($this->invoiceAddress),
        ];
    }
}
