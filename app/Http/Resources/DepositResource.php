<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class DepositResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'quantity' => $this->quantity,
            'item_id' => $this->item_id,
        ];
    }
}
