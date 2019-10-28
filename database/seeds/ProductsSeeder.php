<?php

use App\Brand;
use App\Category;
use App\Product;
use Bezhanov\Faker\ProviderCollectionHelper;
use Faker\Factory;
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
        $faker = Factory::create('pl_PL');
        ProviderCollectionHelper::addAllProvidersTo($faker);

        Brand::create([
            'id' => 1,
            'name' => 'Depth',
            'link' => 'depth',
        ]);

        Category::create([
            'id' => 1,
            'name' => 'Łańcuszki',
            'link' => 'chains',
        ]);

        Category::create([
            'id' => 2,
            'name' => 'Sygnety',
            'link' => 'rings',
        ]);

        Category::create([
            'id' => 3,
            'name' => 'Koszulki',
            'link' => 'tees',
        ]);

        for ($i = 1; $i <= 10; $i++) {

            Product::create([
                'name' => $faker->productName(),
                'price' => rand(100, 200),
                'description' => $faker->paragraph(),
                'brand_id' => 1,
                'category_id' => rand(1, 3),
            ]);
        }
    }
}
