<?php

namespace App\Services;

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
}
