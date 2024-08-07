<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ConsentResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'description_html' => $this->resource->description_html,
            'required' => $this->resource->required,
        ];
    }
}
