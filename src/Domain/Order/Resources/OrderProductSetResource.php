<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\Resource;
use App\Traits\MetadataResource;
use Domain\ProductSet\ProductSet;
use Illuminate\Http\Request;

/**
 * @property ProductSet $resource
 */
final class OrderProductSetResource extends Resource
{
    use MetadataResource;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            ...$this->metadataResource('product_sets.show_metadata_private'),
        ];
    }
}
