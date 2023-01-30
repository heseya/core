<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class EventResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'key' => $this->resource->key,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'required_permissions' => $this->resource->required_permissions,
            'required_hidden_permissions' => $this->resource->required_hidden_permissions,
            'encrypted' => $this->resource->encrypted,
        ];
    }
}
