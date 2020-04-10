<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Order;
use Faker\Generator as Faker;

$factory->define(Order::class, function (Faker $faker) {
    return [
        'code' => $faker->regexify('[A-Z0-9]{6}'),
        'email' => $faker->unique()->safeEmail,
        'payment_status' => 0,
        'shop_status' => 0,
        'delivery_status' => 0,
    ];
});
