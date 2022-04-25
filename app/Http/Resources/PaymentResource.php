<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PaymentResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'external_id' => $this->resource->external_id,
            'method_id' => $this->resource->method_id,
            'status' => $this->resource->status,
            'amount' => $this->resource->amount,
        ];
    }
}
