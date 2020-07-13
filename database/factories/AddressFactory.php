<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Address;
use Faker\Generator as Faker;

$factory->define(Address::class, function (Faker $faker) {
    return [
        'name' => $faker->firstName . ' ' . $faker->lastName,
        'phone' => $faker->phoneNumber,
        'address' => 'Nowogrodzka 84/86',
        'zip' => '02-018',
        'city' => 'Warszawa',
        'country' =>'PL',
        // 'address' => $faker->streetAddress,
        // 'zip' => $faker->postcode,
        // 'city' => $faker->city,
        // 'country' => rand(0, 3) ? 'PL' : $faker->countryCode,
    ];
});
