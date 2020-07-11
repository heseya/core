<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Package;
use Faker\Generator as Faker;

$factory->define(Package::class, function (Faker $faker) {

    $name = $faker->unique()->name;

    return [
        'name' => $name . ' package',
        'weight' => rand(1, 999) / 10.0,
        'width' => rand(1, 100),
        'height' => rand(1, 100),
        'depth' => rand(1, 100),
    ];
});
