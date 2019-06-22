<?php

use Illuminate\Database\Seeder;

use App\Order;
use App\Address;

class OrdersSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $faker = \Faker\Factory::create('pl_PL');

    for ($i = 1; $i <= 20; $i++) {

      $order = Order::create([
        'code' => $faker->regexify('[A-Z0-9]{6}'),
        'payment' => 1,
        'payment_status' => 0,
        'shop_status' => 0,
        'delivery' => 1,
        'delivery_status' => 0
      ]);

      $order->address()->save(new Address([
        'name' => $faker->firstName() . ' ' . $faker->lastName(),
        'address' => $faker->streetAddress(),
        'zip' => $faker->postcode(),
        'city' => $faker->city(),
        'country' => 'PL'
      ]));
    }
  }
}
