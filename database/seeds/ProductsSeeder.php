<?php

use Illuminate\Database\Seeder;

use App\Product;

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

    for ($i = 1; $i <= 10; $i++) {

      Product::create([
        'name' => $faker->colorName() . ' ' . $faker->firstNameMale(),
        'category' => 0
      ]);
    }
  }
}
