<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SchemaItemResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'value' => $this->value,
            'extra_price' => $this->extra_price,
            'item' => ItemResource::make($this->item),
        ];
    }
}
