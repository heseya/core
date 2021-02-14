<?php

namespace App\Http\Resources\Schemas;

use App\Http\Resources\Resource;
use Illuminate\Http\Request;

class BooleanSchemaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'type' => 'boolean',
            'id' => $this->getKey(),
            'name' => $this->getName(),
            'price' => $this->price,
        ];
    }
}
