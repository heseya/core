<?php

namespace App\Services;

use App\Models\Metadata;
use App\Models\Product;
use App\Models\ProductAttribute;
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
            'slug' => $product->slug,
            'hide_on_index' => $this->mapHideOnIndex($product),
            'available' => $product->available,
            'price' => $product->price,
            'price_min' => $product->price_min,
            'price_max' => $product->price_max,
            'public' => $product->public,
            'description' => strip_tags($product->description_html),
            'description_short' => $product->description_short,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
            'order' => $product->order,
            'tags' => $product->tags->map(fn (Tag $tag): array => $this->mapTag($tag))->toArray(),
            'sets' => $this->mapSets($product),
            'attributes' => ProductAttribute::where('product_id', $product->getKey())
                ->with('options')
                ->get()
                ->map(fn (ProductAttribute $attribute): array => $this->mapAttribute($attribute))
                ->toArray(),
            'metadata' => Metadata::where('model_id', $product->getKey())
                ->where('model_type', Product::class)
                ->get()
                ->map(fn (Metadata $meta): array => $this->mapMeta($meta))
                ->toArray(),
        ];
    }

    private function mapHideOnIndex(Product $product): bool
    {
        $sets = $this->productSetService->flattenParentsSetsTree($product->sets);

        foreach ($sets as $set) {
            if ($set->hide_on_index) {
                return true;
            }
        }

        return false;
    }

    private function mapTag(Tag $tag): array
    {
        return [
            'id' => $tag->getKey(),
            'name' => $tag->name,
        ];
    }

    private function mapSets(Product $product): array
    {
        $sets = $this->productSetService->flattenParentsSetsTree($product->sets);

        return $sets->map(fn (ProductSet $set): array => $this->mapSet($set))->toArray();
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

    private function mapAttribute(ProductAttribute $attribute): array
    {
        return [
            'id' => $attribute->attribute->getKey(),
            'name' => $attribute->attribute->name,
            'slug' => $attribute->attribute->slug,
            'type' => $attribute->attribute->type,
            'values' => $attribute->options->map(function ($option): array {
                return [
                    'id' => $option->getKey(),
                    'name' => $option->name,
                    'value_number' => $option->value_number,
                    'value_date' => $option->value_date,
                ];
            })->toArray(),
        ];
    }

    private function mapMeta(Metadata $meta): array
    {
        return [
            'id' => $meta->getKey(),
            'name' => $meta->name,
            'value' => (string) $meta->value,
            'value_type' => $meta->value_type,
            'public' => $meta->public,
        ];
    }
}
