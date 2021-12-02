<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class EventResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'required_permissions' => $this->required_permissions,
            'required_hidden_permissions' => $this->required_hidden_permissions,
        ];
    }
}
