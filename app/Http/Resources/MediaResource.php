<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'type' => Str::lower($this->type->key),
            'url' => $this->url,
            'slug' => $this->slug,
            'alt' => $this->alt,
        ];
    }
}
