<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Page;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(Page::class, function (Faker $faker) {

    $name = $faker->unique()->country;

    return [
        'name' => $name,
        'slug' => Str::slug($name),
        'public' => rand(0, 1),
        'content_md' => $faker->sentence(20),
    ];
});
