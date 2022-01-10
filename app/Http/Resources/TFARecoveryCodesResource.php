<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TFARecoveryCodesResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'recovery_codes' => collect($this->resource)->toArray(),
        ];
    }
}
