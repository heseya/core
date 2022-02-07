<?php

namespace App\Exceptions;

use App\Http\Resources\Resource;
use Illuminate\Support\Collection;

final class TFAExceptionResource extends Resource
{
    public function base($request): array
    {
        return Collection::make($this->resource)->toArray();
    }
}
