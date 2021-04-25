<?php

namespace App\Http\Resources;

class AppResource extends Resource
{
    public function base($request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'url' => $this->url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
