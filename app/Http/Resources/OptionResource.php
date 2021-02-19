<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OptionResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'price' => $this->price,
            'disabled' => $this->disabled,
            'available' => $this->available,
            'items' => ItemResource::collection($this->items),
        ];
    }
}
