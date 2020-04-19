<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\ProductSchema;
use Faker\Generator as Faker;

$factory->define(ProductSchema::class, function (Faker $faker) {
    return [
        'name' => $faker->randomElement([
            'Łańcuszek',
            'Zawieszka',
            'Grawer',
            'Typ',
            'Kolor',
        ]),
        'type' => rand(0, 1),
        'required' => rand(0, 1),
    ];
});
