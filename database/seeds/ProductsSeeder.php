<?php

use App\Brand;
use App\Product;
use App\Category;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Bezhanov\Faker\ProviderCollectionHelper;

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
            'slug' => 'depth',
        ]);

        Category::create([
            'id' => 1,
            'name' => 'Łańcuszki',
            'slug' => 'chains',
        ]);

        Category::create([
            'id' => 2,
            'name' => 'Sygnety',
            'slug' => 'rings',
        ]);

        Category::create([
            'id' => 3,
            'name' => 'Koszulki',
            'slug' => 'tees',
        ]);

        for ($i = 1; $i <= 10; $i++) {

            $name = $faker->productName();

            Product::create([
                'name' => $name,
                'slug' => strtolower(str_replace(' ', '-', $name)),
                'price' => rand(100, 200),
                'description' => $faker->paragraph(),
                'brand_id' => 1,
                'category_id' => rand(1, 3),
            ]);
        }
    }
}
