<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class DiscountResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'code' => $this->code,
            'description' => $this->description,
            'discount' => $this->discount,
            'type' => $this->type,
            'uses' => $this->uses,
            'max_uses' => $this->max_uses,
            'available' => $this->available,
        ];
    }
}
