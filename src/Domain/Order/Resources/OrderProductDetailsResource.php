<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\MediaResource;
use App\Http\Resources\ProductItemResource;
use App\Http\Resources\Resource;
use App\Models\Product;
use App\Traits\MetadataResource;
use Domain\ProductSet\ProductSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @property Product $resource
 */
final class OrderProductDetailsResource extends Resource
{
    use MetadataResource;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        $data = [
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'cover' => MediaResource::make($this->resource->media->first()),
        ];

        return array_merge(
            $data,
            $this->metadataResource('products.show_metadata_private'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function view(Request $request): array
    {
        $sets = Gate::denies('product_sets.show_hidden')
            ? $this->resource->sets->filter(
                fn (ProductSet $set) => $set->public === true && $set->public_parent === true,
            )
            : $this->resource->sets;

        return [
            'gallery' => MediaResource::collection($this->resource->media),
            'items' => ProductItemResource::collection($this->resource->items),
            'sets' => OrderProductSetResource::collection($sets),
            'attributes' => OrderProductAttributeResource::collection($this->resource->productAttributes),
        ];
    }
}
