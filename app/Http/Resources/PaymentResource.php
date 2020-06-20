<?php

namespace App\Http\Resources;

class PaymentResource extends Resource
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
            'method' => $this->method,
            'status' => $this->status,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'redirect_url' => $this->redirect_url,
            'continue_url' => $this->continue_url,
        ];
    }
}
