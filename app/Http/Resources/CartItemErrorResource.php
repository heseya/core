<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CartItemErrorResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'key' => $this->resource->key,
            'message' => $this->resource->message,
        ];
    }
}
