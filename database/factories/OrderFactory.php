<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Order;
use Faker\Generator as Faker;

$factory->define(Order::class, function (Faker $faker) {
    return [
        'code' => $faker->regexify('[A-Z0-9]{6}'),
        'email' => $faker->unique()->safeEmail,
        'currency' => $faker->currencyCode,
        'status_id' => rand(1, 3),
        'shipping_method_id' => rand(1, 4),
        'shipping_price' => rand(8, 20) + 0.99,
    ];
});
