<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Category;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use Bezhanov\Faker\ProviderCollectionHelper;

$factory->define(Category::class, function (Faker $faker) {

    ProviderCollectionHelper::addAllProvidersTo($faker);

    $name = $faker->unique()->randomElement([
        'Akcesoria',
        'Biżuteria',
        'Narzędzia',
        'Obuwie',
        'Okulary',
        'Ubrania',
        'Zegarki',
    ]);

    return [
        'name' => $name,
        'slug' => Str::slug($name),
        'public' => rand(0, 1),
    ];
});
