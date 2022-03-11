<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PaymentResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'method' => $this->method,
            'paid' => $this->paid,
            'amount' => $this->amount,
            'date' => $this->created_at,
            'redirect_url' => $this->redirect_url,
            'continue_url' => $this->continue_url,
        ];
    }
}
