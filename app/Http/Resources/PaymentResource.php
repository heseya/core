<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PaymentResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'method' => $this->resource->method,
            'paid' => $this->resource->paid,
            'amount' => $this->resource->amount,
            'redirect_url' => $this->resource->redirect_url,
            'continue_url' => $this->resource->continue_url,
        ];
    }
}
