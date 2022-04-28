<?php

namespace App\Exceptions;

use App\Http\Resources\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class TFAExceptionResource extends Resource
{
    public function base(Request $request): array
    {
        /** @var Collection<int, mixed> $resource */
        $resource = $this->resource;

        return Collection::make($resource)->toArray();
    }
}
