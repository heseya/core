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
            'external_id' => $this->external_id,
            'method' => $this->method,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'redirect_url' => $this->redirect_url,
            'continue_url' => $this->continue_url,
        ];
    }
}
