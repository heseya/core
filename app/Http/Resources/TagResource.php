<?php

namespace App\Http\Resources;

class TagResource extends Resource
{
    public function base($request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'color' => $this->color,
        ];
    }
}
