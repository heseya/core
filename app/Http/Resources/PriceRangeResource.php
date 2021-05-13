<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PriceRangeResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'start' => $this->start,
            'prices' => PriceResource::collection($this->prices),
        ];
    }
}
