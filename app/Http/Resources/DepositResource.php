<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class DepositResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'quantity' => (float) $this->resource->quantity,
            'item_id' => $this->resource->item_id,
            'sku' => $this->resource->item?->sku,
            'created_at' => $this->resource->created_at,
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'order' => OrderShortResource::make($this->resource->order),
        ];
    }
}
