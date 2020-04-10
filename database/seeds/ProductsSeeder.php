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
        factory(Category::class, 5)->create();
        factory(Brand::class, 6)->create();

        factory(Product::class, 100)->create([
            'category_id' => rand(1, 5),
            'brand_id' => rand(1, 6),
        ]);
    }
}
