<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ShippingMethodResource extends Resource
{
    private ?float $fixedPrice = null;

    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'price' => $this->fixedPrice ?? $this->price,
            'public' => $this->public,
            'payment_methods' => PaymentMethodResource::collection($this->paymentMethods),
        ];
    }

    public function setPrice(?float $price): self
    {
        $this->fixedPrice = $price;

        return $this;
    }
}
