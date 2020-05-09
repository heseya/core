<?php

use App\Models\Item;
use App\Models\Brand;
use App\Models\Media;
use App\Models\Deposit;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductSchema;
use Illuminate\Database\Seeder;
use App\Models\ProductSchemaItem;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Category::class, 3)->create(['public' => true]);
        factory(Brand::class, 4)->create(['public' => true]);

        factory(Category::class, 2)->create(['public' => false]);
        factory(Brand::class, 2)->create(['public' => false]);

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

            $schema->schemaItems()->create([
                'item_id' => $item->id,
                'extra_price' => 0,
            ]);
        }

        function complexProduct($product)
        {
            $product->schemas()->saveMany(factory(ProductSchema::class, rand(0, 4))->make())->each(function ($schema) {
                $schema->schemaItems()->saveMany(factory(ProductSchemaItem::class, rand(1, 3))->make())->each(function ($schemaItem) {
                    $item = factory(Item::class)->create();
                    $item->deposits()->saveMany(factory(Deposit::class, rand(0, 2))->make());
                    $schemaItem->item()->associate($item)->save();
                });
            });
        }

        function media($product)
        {
            for ($i = 0; $i < rand(0, 5); $i++) {
                $media = factory(Media::class)->create();
                $product->media()->attach($media);
            }
        }

        factory(Product::class, 25)->create([
            'category_id' => rand(1, 3),
            'brand_id' => rand(1, 4),
            'public' => true,
        ])->each('simpleProduct')->each('media');

        factory(Product::class, 25)->create([
            'category_id' => rand(1, 3),
            'brand_id' => rand(1, 4),
            'public' => false,
        ])->each('simpleProduct')->each('media');

        factory(Product::class, 25)->create([
            'category_id' => rand(1, 3),
            'brand_id' => rand(1, 4),
            'public' => true,
        ])->each('complexProduct')->each('media');

        factory(Product::class, 25)->create([
            'category_id' => rand(1, 3),
            'brand_id' => rand(1, 4),
            'public' => false,
        ])->each('complexProduct')->each('media');

        factory(Product::class, 25)->create([
            'category_id' => rand(3, 5),
            'brand_id' => rand(4, 6),
            'public' => true,
        ])->each('simpleProduct')->each('media');

        factory(Product::class, 25)->create([
            'category_id' => rand(3, 5),
            'brand_id' => rand(4, 6),
            'public' => false,
        ])->each('simpleProduct')->each('media');

        factory(Product::class, 25)->create([
            'category_id' => rand(3, 5),
            'brand_id' => rand(4, 6),
            'public' => true,
        ])->each('complexProduct')->each('media');

        factory(Product::class, 25)->create([
            'category_id' => rand(3, 5),
            'brand_id' => rand(4, 6),
            'public' => false,
        ])->each('complexProduct')->each('media');
    }
}
