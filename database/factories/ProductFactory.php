<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Product;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use Bezhanov\Faker\ProviderCollectionHelper;

$factory->define(Product::class, function (Faker $faker) {

    ProviderCollectionHelper::addAllProvidersTo($faker);

    $name = $faker->unique()->productName;

    return [
        'name' => $name,
        'slug' => Str::slug($name),
        'price' => round(rand(500, 6000), -2),
        'description' => $faker->realText,
    ];
});
