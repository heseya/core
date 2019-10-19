<?php

use App\Brand;
use App\Product;
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
            'name' => 'Depth',
            'link' => 'depth',
        ]);

        for ($i = 1; $i <= 10; $i++) {

            Product::create([
                'name' => $faker->productName(),
                'description' => $faker->paragraph(),
            ]);
        }
    }
}
