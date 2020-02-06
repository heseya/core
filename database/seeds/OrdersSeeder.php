<?php

use App\Order;
use App\Address;
use Illuminate\Database\Seeder;

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

        for ($i = 1; $i <= 100; $i++) {
            $address = Address::create([
                'name' => $faker->firstName() . ' ' . $faker->lastName(),
                'phone' => $faker->phoneNumber(),
                'address' => $faker->streetAddress(),
                'zip' => $faker->postcode(),
                'city' => $faker->city(),
                'country' => $faker->countryCode(),
            ]);

            Order::create([
                'code' => $faker->regexify('[A-Z0-9]{6}'),
                'email' => $faker->email(),
                'payment_status' => 0,
                'shop_status' => 0,
                'delivery_status' => 0,
                'delivery_address' => $address->id,
                'invoice_address' => rand(0, 1) == 1 ? $address->id : null,
            ]);
        }
    }
}
