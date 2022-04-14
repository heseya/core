<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ConsentUserResource extends ConsentResource
{
    public function base(Request $request): array
    {
        return parent::base($request) + ['value' => $this->resource->pivot->value];
    }
}
