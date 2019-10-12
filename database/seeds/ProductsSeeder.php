<?php

use App\Product;
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
        $faker = \Faker\Factory::create('pl_PL');
        \Bezhanov\Faker\ProviderCollectionHelper::addAllProvidersTo($faker);

        for ($i = 1; $i <= 10; $i++) {

            Product::create([
                'name' => $faker->productName(),
                'description' => $faker->paragraph(),
                'category' => 0,
                'brand' => 0,
            ]);
        }
    }
}
