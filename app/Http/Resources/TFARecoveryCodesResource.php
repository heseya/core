<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TFARecoveryCodesResource extends Resource
{
    public function base(Request $request): array
    {
        /** @var Collection<int, mixed> $resource */
        $resource = $this->resource;

        return [
            'recovery_codes' => Collection::make($resource)->toArray(),
        ];
    }
}
