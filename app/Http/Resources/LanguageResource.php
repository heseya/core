<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class LanguageResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'iso' => $this->resource->iso,
            'name' => $this->resource->name,
            'default' => $this->resource->default,
            'hidden' => $this->resource->hidden,
        ];
    }
}
