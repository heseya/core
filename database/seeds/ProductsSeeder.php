<?php

use App\Tax;
use App\Brand;
use App\Photo;
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

        Tax::create([
            'id' => 1,
            'name' => '23%',
            'value' => 23,
        ]);

        Tax::create([
            'id' => 2,
            'name' => '8%',
            'value' => 8,
        ]);

        Brand::create([
            'id' => 1,
            'name' => 'Depth',
            'slug' => 'depth',
            'public' => 1,
        ]);

        Brand::create([
            'id' => 2,
            'name' => 'Depth Steel',
            'slug' => 'depth-steel',
            'public' => 1,
        ]);

        Category::create([
            'id' => 1,
            'name' => 'Łańcuszki',
            'slug' => 'chains',
            'public' => 1,
        ]);

        Category::create([
            'id' => 2,
            'name' => 'Sygnety',
            'slug' => 'rings',
            'public' => 1,
        ]);

        Category::create([
            'id' => 3,
            'name' => 'Koszulki',
            'slug' => 'tees',
            'public' => 1,
        ]);

        for ($i = 1; $i <= 100; $i++) {

            $name = $faker->randomElement([
                'Snake',
                'Half-hearth',
                'Doberman',
                'Moon',
                'Bat',
                'Tribal',
                'Hangskeleton',
                'Coffin',
                'Innocente',
                'Reduction',
                'Automaton',
                'Cry Baby',
                'Pancer',
                'Benz',
                'Eagle',
            ]);

            $product = Product::create([
                'name' => $name,
                'slug' => strtolower(str_replace(' ', '-', $name)) . '-' . rand(1, 9999),
                'price' => rand(100, 200),
                'description' => $faker->paragraph(),
                'tax_id' => rand(1, 2),
                'brand_id' => 1,
                'category_id' => rand(1, 3),
            ]);

            $product->gallery()->attach([
                Photo::create([
                    'url' => $faker->randomElement([
                        'https://kupdepth.pl/img/products/174.jpeg',
                        'https://kupdepth.pl/img/products/283.jpeg',
                        'https://kupdepth.pl/img/products/275.jpeg',
                        'https://kupdepth.pl/img/products/275.jpeg',
                        'https://kupdepth.pl/img/products/275.jpeg',
                        'https://kupdepth.pl/img/products/295.jpeg',
                    ]),
                ])->id
            ]);
        }
    }
}
