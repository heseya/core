<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Brand;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use Bezhanov\Faker\ProviderCollectionHelper;

$factory->define(Brand::class, function (Faker $faker) {

    ProviderCollectionHelper::addAllProvidersTo($faker);

    $name = $faker->unique()->deviceManufacturer;

    return [
        'name' => $name,
        'slug' => Str::slug($name),
        'public' => rand(0, 1),
    ];
});
