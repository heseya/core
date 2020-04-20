<?php

use App\Item;
use App\Brand;
use App\Deposit;
use App\Product;
use App\Category;
use App\ProductSchema;
use App\ProductSchemaItem;
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
        factory(Category::class, 3)->create(['public' => true]);
        factory(Brand::class, 4)->create(['public' => true]);

        factory(Category::class, 2)->create(['public' => false]);
        factory(Brand::class, 2)->create(['public' => false]);

        factory(Product::class, 50)->create([
            'category_id' => rand(1, 3),
            'brand_id' => rand(1, 4),
            'public' => true,
        ])->each(function ($product) {
            $product->schemas()->saveMany(factory(ProductSchema::class, rand(0, 4))->make())->each(function ($schema) {
                $schema->schemaItems()->saveMany(factory(ProductSchemaItem::class, rand(1, 3))->make())->each(function ($schemaItem) {
                    $item = factory(Item::class)->create();
                    $item->deposits()->saveMany(factory(Deposit::class, rand(0, 2))->make());
                    $schemaItem->item()->associate($item)->save();
                });
            });
        });

        factory(Product::class, 50)->create([
            'category_id' => rand(3, 5),
            'brand_id' => rand(4, 6),
            'public' => true,
        ])->each(function ($product) {
            $product->schemas()->saveMany(factory(ProductSchema::class, rand(0, 4))->make())->each(function ($schema) {
                $schema->schemaItems()->saveMany(factory(ProductSchemaItem::class, rand(1, 3))->make())->each(function ($schemaItem) {
                    $item = factory(Item::class)->create();
                    $item->deposits()->saveMany(factory(Deposit::class, rand(0, 2))->make());
                    $schemaItem->item()->associate($item)->save();
                });
            });
        });

        factory(Product::class, 200)->create([
            'category_id' => rand(1, 4),
            'brand_id' => rand(1, 5),
        ])->each(function ($product) {
            $product->schemas()->saveMany(factory(ProductSchema::class, rand(0, 4))->make())->each(function ($schema) {
                $schema->schemaItems()->saveMany(factory(ProductSchemaItem::class, rand(1, 3))->make())->each(function ($schemaItem) {
                    $schemaItem->item()->associate(factory(Item::class)->create())->save();
                });
            });
        });
    }
}
