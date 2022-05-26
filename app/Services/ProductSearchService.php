<?php

namespace App\Services;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Media;
use App\Models\Metadata;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Tag;
use App\Services\Contracts\ProductSearchServiceContract;
use App\Services\Contracts\ProductSetServiceContract;

class ProductSearchService implements ProductSearchServiceContract
{
    public function __construct(
        private ProductSetServiceContract $productSetService,
    ) {
    }

    public function mapSearchableArray(Product $product): array
    {
        return [
            'id' => $product->getKey(),
            'name' => $product->name,
            'name_text' => $product->name,
            'slug' => $product->slug,
            'google_product_category' => $product->google_product_category,
            'hide_on_index' => $this->mapHideOnIndex($product),
            'available' => $product->available,
            'public' => $product->public,
            'price' => $product->price,
            'price_min' => $product->price_min,
            'price_max' => $product->price_max,
            'price_min_initial' => $product->price_min_initial,
            'price_max_initial' => $product->price_max_initial,
            'description' => strip_tags($product->description_html),
            'description_short' => $product->description_short,
            'created_at' => $product->created_at->toIso8601String(),
            'updated_at' => $product->updated_at->toIso8601String(),
            'order' => $product->order,
            'cover' => $this->mapCover($product),
            'tags_id' => $product->tags->map(fn (Tag $tag): string => $tag->getKey())->toArray(),
            'tags' => $product->tags->map(fn (Tag $tag): array => $this->mapTag($tag))->toArray(),
            'sets_slug' => $this->mapSetsSlugs($product),
            'sets' => $this->mapSets($product),
            'attributes' => $product->attributes
                ->map(fn (Attribute $attribute): array => $this->mapAttribute($attribute))
                ->toArray(),
            'attributes_text' => $product->attributes
                ->map(fn (Attribute $attribute): array => $this->getAttributeValue($attribute))
                ->flatten()
                ->toArray(),
            'metadata' => $product->metadata
                ->map(fn (Metadata $meta): array => $this->mapMeta($meta))
                ->toArray(),
            'metadata_private' => $product->metadataPrivate
                ->map(fn (Metadata $meta): array => $this->mapMeta($meta))
                ->toArray(),
        ];
    }

    public function mappableAs(): array
    {
        return [
            'id' => 'keyword',
            'name' => 'keyword',
            'name_text' => 'text',
            'slug' => 'text',
            'google_product_category' => 'integer',
            'hide_on_index' => 'boolean',
            'available' => 'boolean',
            'public' => 'boolean',
            'price' => 'float',
            'price_min' => 'float',
            'price_max' => 'float',
            'price_min_initial' => 'float',
            'price_max_initial' => 'float',
            'description' => 'text',
            'description_short' => 'text',
            'created_at' => 'date',
            'updated_at' => 'date',
            'order' => 'integer',

            'cover' => 'flattened',

            'tags_id' => 'keyword',
            'tags' => 'flattened',

            'sets_slug' => 'keyword',
            'sets' => 'flattened',

            'attributes' => [
                'id' => 'keyword',
                'name' => 'text',
                'slug' => 'text',
                'attribute_type' => 'text',
                'values' => [
                    'id' => 'keyword',
                    'name' => 'text',
                    'value_number' => 'float',
                    'value_date' => 'date',
                    'metadata' => 'flattened',
                    'metadata_private' => 'flattened',
                ],
            ],
            'attributes_text' => 'text',
            'metadata' => 'flattened',
            'metadata_private' => 'flattened',
        ];
    }

    private function mapHideOnIndex(Product $product): bool
    {
        $sets = $this->productSetService->flattenParentsSetsTree($product->sets);

        return $sets->contains(fn (ProductSet $set) => $set->hide_on_index);
    }

    private function mapCover(Product $product): ?array
    {
        /** @var ?Media $cover */
        $cover = $product->media()->first();

        if ($cover === null) {
            return null;
        }

        return [
            'id' => $cover->getKey(),
            'url' => $cover->url,
            'type' => $cover->type,
            'metadata' => $cover->metadata
                ->map(fn (Metadata $meta): array => $this->mapMeta($meta))
                ->toArray(),
            'metadata_private' => $cover->metadataPrivate
                ->map(fn (Metadata $meta): array => $this->mapMeta($meta))
                ->toArray(),
        ];
    }

    private function mapSetsSlugs(Product $product): array
    {
        $sets = $this->productSetService->flattenParentsSetsTree($product->sets);

        return $sets->map(fn (ProductSet $set): string => $set->slug)->toArray();
    }

    private function mapSets(Product $product): array
    {
        $sets = $this->productSetService->flattenParentsSetsTree($product->sets);

        return $sets->map(fn (ProductSet $set): array => $this->mapSet($set))->toArray();
    }

    private function mapTag(Tag $tag): array
    {
        return [
            'id' => $tag->getKey(),
            'name' => $tag->name,
            'color' => $tag->color,
        ];
    }

    private function mapSet(ProductSet $set): array
    {
        return [
            'id' => $set->getKey(),
            'name' => $set->name,
            'slug' => $set->slug,
            'description' => strip_tags($set->description_html),
        ];
    }

    private function mapAttribute(Attribute $attribute): array
    {
        return [
            'id' => $attribute->getKey(),
            'name' => $attribute->name,
            'slug' => $attribute->slug,
            'attribute_type' => $attribute->type,
            'values' => $attribute->pivot->options->map(fn (AttributeOption $option): array => [
                'id' => $option->getKey(),
                'name' => $option->name,
                'value_number' => $option->value_number,
                'value_date' => $option->value_date,
                'metadata' => $option->metadata
                    ->map(fn (Metadata $meta): array => $this->mapMeta($meta))
                    ->toArray(),
                'metadata_private' => $option->metadataPrivate
                    ->map(fn (Metadata $meta): array => $this->mapMeta($meta))
                    ->toArray(),
            ])->toArray(),
            'metadata' => $attribute->metadata
                ->map(fn (Metadata $meta): array => $this->mapMeta($meta))
                ->toArray(),
            'metadata_private' => $attribute->metadataPrivate
                ->map(fn (Metadata $meta): array => $this->mapMeta($meta))
                ->toArray(),
        ];
    }

    private function getAttributeValue(Attribute $attribute): array
    {
        return match ($attribute->type->value) {
            AttributeType::NUMBER => $attribute->pivot->options->pluck('value_number')->toArray(),
            AttributeType::DATE => $attribute->pivot->options->pluck('value_date')->toArray(),
            default => $attribute->pivot->options->pluck('name')->toArray(),
        };
    }

    private function mapMeta(Metadata $meta): array
    {
        return [
            'id' => $meta->getKey(),
            'name' => $meta->name,
            'value' => $meta->value,
            'value_type' => $meta->value_type,
        ];
    }
}
