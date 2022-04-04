<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class DepositResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'quantity' => $this->resource->quantity,
            'item_id' => $this->resource->item_id,
        ];
    }
}
