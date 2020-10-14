<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductSchema;
use App\Models\ProductSchemaItem;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = array_merge(
            Category::factory()->count(3)->create(['public' => true])->all(),
            Category::factory()->count(2)->create(['public' => false])->all(),
        );

        $brands = array_merge(
            Brand::factory()->count(4)->create(['public' => true])->all(),
            Brand::factory()->count(2)->create(['public' => false])->all(),
        );

        function simpleProduct ($product) {
            $schema = $product->schemas()->create([
                'name' => null,
                'type' => 0,
                'required' => true,
            ]);

            $item = Item::create([
                'name' => $product->name,
                'sku' => null,
            ]);

            $item->deposits()->saveMany(Deposit::factory()->count(rand(0, 2))->make());

            $schema->schemaItems()->create([
                'item_id' => $item->getKey(),
                'extra_price' => 0,
            ]);
        }

        function complexProduct($product)
        {
            $product->schemas()->saveMany(ProductSchema::factory()->count(rand(0, 4))->make())->each(function ($schema) {
                $schema->schemaItems()->saveMany(ProductSchemaItem::factory()->count(rand(1, 3))->make())->each(function ($schemaItem) {
                    $item = Item::factory()->create();
                    $item->deposits()->saveMany(Deposit::factory()->count(rand(0, 2))->make());
                    $schemaItem->item()->associate($item)->save();
                });
            });
        }

        function media($product)
        {
            for ($i = 0; $i < rand(0, 5); $i++) {
                $media = Media::factory()->create();
                $product->media()->attach($media);
            }
        }

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(0, 2)]->getKey(),
            'brand_id' => $brands[rand(0, 3)]->getKey(),
            'public' => true,
        ])->each('simpleProduct')->each('media');

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(0, 2)]->getKey(),
            'brand_id' => $brands[rand(0, 3)]->getKey(),
            'public' => false,
        ])->each('simpleProduct')->each('media');

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(0, 2)]->getKey(),
            'brand_id' => $brands[rand(0, 3)]->getKey(),
            'public' => true,
        ])->each('complexProduct')->each('media');

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(0, 2)]->getKey(),
            'brand_id' => $brands[rand(0, 3)]->getKey(),
            'public' => false,
        ])->each('complexProduct')->each('media');

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(0, 2)]->getKey(),
            'brand_id' => $brands[rand(0, 3)]->getKey(),
            'public' => true,
        ])->each('simpleProduct')->each('media');

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(2, 4)]->getKey(),
            'brand_id' => $brands[rand(3, 5)]->getKey(),
            'public' => false,
        ])->each('simpleProduct')->each('media');

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(2, 4)]->getKey(),
            'brand_id' => $brands[rand(3, 5)]->getKey(),
            'public' => true,
        ])->each('complexProduct')->each('media');

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(2, 4)]->getKey(),
            'brand_id' => $brands[rand(3, 5)]->getKey(),
            'public' => false,
        ])->each('complexProduct')->each('media');
    }
}
