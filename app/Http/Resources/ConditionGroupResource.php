<?php

namespace App\Http\Resources;

use App\Models\ConditionGroup;
use Illuminate\Http\Request;

/**
 * @property ConditionGroup $resource
 */
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
