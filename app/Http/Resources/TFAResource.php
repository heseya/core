<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TFAResource extends Resource
{
    public function base(Request $request): array
    {
        return Collection::make($this->resource)->toArray();
    }
}
