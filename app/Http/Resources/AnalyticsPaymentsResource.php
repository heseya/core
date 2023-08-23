<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AnalyticsPaymentsResource extends Resource
{
    public function base(Request $request): array
    {
        /** @var Collection<int, mixed> $resource */
        $resource = $this->resource;

        return Collection::make($resource)->map(
            fn (array $items) => collect($items)->map(fn ($item) => [
                'amount' => $item['amount'],
                'count' => $item['count'],
                'currency' => $item['currency'],
            ])->toArray()
        )->toArray();
    }
}
