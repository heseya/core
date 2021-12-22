<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeoKeywordsResource extends Resource
{
    public function base(Request $request): array
    {
        $duplicates = collect($this->resource)->map(fn ($item) => [
            'id' => $item['model_id'],
            'model_type' => Str::afterLast($item['model_type'], '\\'),
        ]);
        return [
            'duplicated' => $duplicates->isNotEmpty(),
            'duplicates' => $duplicates->toArray(),
        ];
    }
}
