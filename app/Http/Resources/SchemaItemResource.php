<?php

namespace App\Http\Resources;

use App\Http\Resources\ItemResource;

class SchemaItemResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function base($request): array
    {
        return [
            'id' => $this->id,
            'value' => $this->value,
            'extra_price' => $this->extra_price,
            'item' => new ItemResource($this->item),
        ];
    }
}
