<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Status;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(Status::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->word,
        'color' => ltrim($faker->hexcolor, '#'),
        'description' => Str::limit($faker->paragraph, 220),
    ];
});
