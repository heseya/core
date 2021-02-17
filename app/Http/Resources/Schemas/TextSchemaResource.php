<?php

namespace App\Http\Resources\Schemas;

use App\Http\Resources\Resource;
use Illuminate\Http\Request;

class TextSchemaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'type' => 'text',
            'id' => $this->getKey(),
            'name' => $this->name,
            'price' => $this->price,
            'validation' => $this->validation,
        ];
    }
}
