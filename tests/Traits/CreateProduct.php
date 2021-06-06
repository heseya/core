<?php

namespace Tests\Traits;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

trait CreateProduct
{
    public function createProduct(array $payload = []): Product
    {
        $brand = Brand::factory()->create([
            'public' => true,
        ]);

        $category = Category::factory()->create([
            'public' => true,
        ]);

        return Product::factory()->create([
            'public' => true,
            'brand_id' => $brand->getKey(),
            'category_id' => $category->getKey(),
        ] + $payload);
    }
}
