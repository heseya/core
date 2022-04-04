<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserIssuerResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'email' => $this->resource->email,
            'name' => $this->resource->name,
            'avatar' => $this->resource->avatar,
        ];
    }
}
