<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Item;
use Faker\Generator as Faker;
use Bezhanov\Faker\ProviderCollectionHelper;

$factory->define(Item::class, function (Faker $faker) {

    ProviderCollectionHelper::addAllProvidersTo($faker);

    return [
        'name' => $faker->unique()->productName,
        'sku' => $faker->regexify('[A-Z0-9]{4}\/[A-Z0-9]{2}'),
    ];
});
