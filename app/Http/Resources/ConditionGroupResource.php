<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ConditionGroupResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'conditions' => ConditionResource::collection($this->resource->conditions),
        ];
    }
}
