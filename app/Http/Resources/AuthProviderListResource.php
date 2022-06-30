<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AuthProviderListResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'key' => $this->resource->key,
            'active' => $this->resource->active,
        ];
    }
}
