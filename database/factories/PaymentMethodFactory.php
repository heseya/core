<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\PaymentMethod;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(PaymentMethod::class, function (Faker $faker) {

    $name = $faker->randomElement([
        'Przelewy24',
        'Bluemedia',
        'PayNow',
    ]);

    return [
        'name' => $name,
        'alias' => Str::slug($name),
        'public' => rand(0, 1),
    ];
});
