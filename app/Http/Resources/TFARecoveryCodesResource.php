<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TFARecoveryCodesResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'recovery_codes' => Collection::make($this->resource)->toArray(),
        ];
    }
}
