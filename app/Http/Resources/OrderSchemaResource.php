<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderSchemaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'value' => $this->value,
            'price' => $this->price,
        ];
    }
}
