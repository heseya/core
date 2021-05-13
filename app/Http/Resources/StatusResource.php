<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class StatusResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'color' => $this->color,
            'cancel' => $this->cancel,
            'description' => $this->description,
        ];
    }
}
