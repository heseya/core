<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\EventResourceSwagger;
use Illuminate\Http\Request;

class EventResource extends Resource implements EventResourceSwagger
{
    public function base(Request $request): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
