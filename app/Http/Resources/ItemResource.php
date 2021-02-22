<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ItemResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
        ];
    }
}
