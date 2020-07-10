<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Package;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use Bezhanov\Faker\ProviderCollectionHelper;

$factory->define(Package::class, function (Faker $faker) {

    ProviderCollectionHelper::addAllProvidersTo($faker);

    $name = $faker->unique()->name;

    return [
        'name' => $name . ' package',
        'weight' => rand(1, 999) / 10.0,
        'width' => rand(1, 100),
        'height' => rand(1, 100),
        'depth' => rand(1, 100),
    ];
});
