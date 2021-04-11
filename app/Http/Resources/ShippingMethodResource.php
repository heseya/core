<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ShippingMethodResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'price' => $this->price,
            'public' => $this->public,
            'payment_methods' => PaymentMethodResource::collection($this->paymentMethods),
        ];
    }
}
