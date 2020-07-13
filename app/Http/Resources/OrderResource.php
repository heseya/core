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
        return [
            'id' => $this->id,
            'code' => $this->code,
            'email' => $this->email,
            'currency' => $this->currency,
            'summary' => $this->summary,
            'summary_payed' => $this->payed,
            'payed' => $this->isPayed(),
            'created_at' => $this->created_at,
            'status' => StatusResource::make($this->status),
            'delivery_address' => AddressResource::make($this->deliveryAddress),
        ];
    }

    public function view($request): array
    {
        return [
            'invoice_address' => AddressResource::make($this->invoiceAddress),
            'shipping_method' => [
                'id' => $this->shippingMethod->id,
                'name' => $this->shippingMethod->name,
                'price' => $this->shipping_price,
            ],
            'comment' => $this->comment,
            'items' => OrderItemResource::collection($this->items),
            'payments' => PaymentResource::collection($this->payments),
            'shipping_number' => $this->shipping_number,
        ];
    }
}
