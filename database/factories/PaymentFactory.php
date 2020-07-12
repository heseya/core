<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Payment;
use Faker\Generator as Faker;

$factory->define(Payment::class, function (Faker $faker) {
    return [
        'external_id' => rand(0, 9999999),
        'method' => $faker->randomElement(['przelewy24', 'bluemedia', 'paynow']),
        'payed' => rand(0, 1),
        'amount' => rand(10, 1000),
        'redirect_url' => 'https://heseya.com/pay',
        'continue_url' => 'https://store.heseya.com/done',
    ];
});
