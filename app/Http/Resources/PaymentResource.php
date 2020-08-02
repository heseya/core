<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PaymentResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'external_id' => $this->external_id,
            'method' => $this->method,
            'payed' => $this->payed,
            'amount' => $this->amount,
            'redirect_url' => $this->redirect_url,
            'continue_url' => $this->continue_url,
        ];
    }
}
