<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AnalyticsPaymentsResource extends Resource
{
    public function base(Request $request): array
    {
        return collect($this->resource)->map(fn ($item) => [
            'amount' => $item['amount'],
            'count' => $item['count'],
        ])->toArray();
    }
}
