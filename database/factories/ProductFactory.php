<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Product;
use Faker\Generator as Faker;
use Bezhanov\Faker\ProviderCollectionHelper;

$factory->define(Product::class, function (Faker $faker) {

    ProviderCollectionHelper::addAllProvidersTo($faker);

    $name = $faker->unique()->productName;

    return [
        'name' => $name,
        'slug' => strtolower(str_replace(' ', '-', $name)),
        'price' => rand(100, 200),
        'description' => $faker->paragraph,
        'brand_id' => 1,
        'category_id' => rand(1, 3),
    ];
});
