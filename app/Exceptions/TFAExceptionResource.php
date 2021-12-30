<?php

namespace App\Exceptions;

use App\Http\Resources\Resource;

final class TFAExceptionResource extends Resource
{
    public function base($request): array
    {
        return collect($this->resource)->toArray();
    }
}
