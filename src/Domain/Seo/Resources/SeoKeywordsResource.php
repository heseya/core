<?php

declare(strict_types=1);

namespace Domain\Seo\Resources;

use App\Http\Resources\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class SeoKeywordsResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        /** @var Collection<int, mixed> $resource */
        $resource = $this->resource;

        $duplicates = Collection::make($resource)->map(fn ($item) => [
            'id' => $item['model_id'],
            'model_type' => Str::afterLast($item['model_type'], '\\'),
        ]);

        return [
            'duplicated' => $duplicates->isNotEmpty(),
            'duplicates' => $duplicates->toArray(),
        ];
    }
}
