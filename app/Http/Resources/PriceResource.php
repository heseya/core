<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PriceResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'value' => $this->value,
            'region_id' => $this->region_id,
        ];
    }
}
