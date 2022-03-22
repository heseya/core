<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductItemResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'required_quantity' => $this->pivot->quantity,
        ];
    }
}
