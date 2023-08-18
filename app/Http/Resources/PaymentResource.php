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
            'method' => $this->resource->paymentMethod->name ?? $this->resource->method,
            'method_id' => $this->resource->paymentMethod?->getKey() ?? null,
            'status' => $this->resource->status,
            'amount' => $this->resource->amount->getAmount()->toFloat(),
            'currency' => $this->resource->amount->getCurrency()->getCurrencyCode(),
            'redirect_url' => $this->resource->redirect_url,
            'continue_url' => $this->resource->continue_url,
            'date' => $this->resource->created_at,
        ];
    }
}
