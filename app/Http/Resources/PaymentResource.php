<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'method' => $this->method,
            'status' => $this->status,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'redirectUrl' => $this->redirectUrl,
        ]
    }
}
