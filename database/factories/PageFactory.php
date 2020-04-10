<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Page;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(Page::class, function (Faker $faker) {

    $name = $faker->unique()->catchPhrase;

    return [
        'name' => $name,
        'slug' => Str::slug($name),
        'public' => rand(0, 1),
        'content' => $faker->realText(2000),
    ];
});
