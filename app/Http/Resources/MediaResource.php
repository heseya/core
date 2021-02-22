<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MediaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'type' => 'photo',
            'url' => $this->url,
        ];
    }
}
