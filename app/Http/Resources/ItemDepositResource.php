<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ItemDepositResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'quantity' => (float) $this->resource->quantity,
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'from_unlimited' => $this->resource->from_unlimited,
        ];
    }
}
