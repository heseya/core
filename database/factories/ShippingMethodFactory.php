<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\ShippingMethod;
use Faker\Generator as Faker;

$factory->define(ShippingMethod::class, function (Faker $faker) {
    return [
        'name' => $faker->company,
        'price' => rand(8, 15) + 0.99,
        'public' => rand(0, 1),
    ];
});
