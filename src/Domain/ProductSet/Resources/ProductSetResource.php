<?php

declare(strict_types=1);

namespace Domain\ProductSet\Resources;

use App\Http\Resources\MediaResource;
use App\Http\Resources\Resource;
use App\Traits\GetAllTranslations;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ProductSetResource extends Resource
{
    use GetAllTranslations;
    use MetadataResource;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        $public = Gate::denies('product_sets.show_hidden');
        $children = $public
            ? $this->resource->childrenPublic
            : $this->resource->children;

        $depth = $this->resource->depth ?? (int) $request->get('depth', 0);
        $nestedChildren = $depth > 0 ? ($public ? $this->resource->getPublicChildren($depth) : $this->resource->getChildren($depth)) : collect([]);

        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'slug_suffix' => $this->resource->slugSuffix,
            'slug_override' => $this->resource->slugOverride,
            'public' => $this->resource->public,
            'visible' => $this->resource->public_parent && $this->resource->public,
            'parent_id' => $this->resource->parent_id,
            'children_ids' => $children->map(fn ($child) => $child->getKey())->toArray(),
            'cover' => MediaResource::make($this->resource->media),
            'published' => $this->resource->published,
            'children' => $nestedChildren->count() > 0 ? self::collection($nestedChildren) : [],
            ...$this->metadataResource('product_sets.show_metadata_private'),
            ...$request->boolean('with_translations') ?
                $this->getAllTranslations('product_sets.show_hidden') : [],
        ];
    }
}
