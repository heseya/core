<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Order;
use Faker\Generator as Faker;

$factory->define(Order::class, function (Faker $faker) {
    return [
        'code' => $faker->regexify('[A-Z0-9]{6}'),
        'email' => $faker->unique()->safeEmail,
        'currency' => rand(0, 9) < 1 ? $faker->currencyCode : 'PLN',
        'shipping_price' => rand(8, 20) + 0.99,
    ];
});
