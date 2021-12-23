<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TFAResource extends Resource
{
    public function base(Request $request): array
    {
        return collect($this->resource)->toArray();
    }
}
