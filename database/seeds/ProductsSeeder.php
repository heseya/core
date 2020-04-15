<?php

use App\Brand;
use App\Product;
use App\Category;
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
        ]);

        factory(Product::class, 50)->create([
            'category_id' => rand(3, 5),
            'brand_id' => rand(4, 6),
        ]);

        factory(Product::class, 50)->create([
            'category_id' => rand(1, 5),
            'brand_id' => rand(1, 6),
        ]);
    }
}
