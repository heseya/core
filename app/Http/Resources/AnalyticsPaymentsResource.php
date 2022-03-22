<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AnalyticsPaymentsResource extends Resource
{
    public function base(Request $request): array
    {
        return Collection::make($this->resource)->map(fn ($item) => [
            'amount' => $item['amount'],
            'count' => $item['count'],
        ])->toArray();
    }
}
