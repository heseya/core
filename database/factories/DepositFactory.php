<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Deposit;
use Faker\Generator as Faker;
use Bezhanov\Faker\ProviderCollectionHelper;

$factory->define(Deposit::class, function (Faker $faker) {

    ProviderCollectionHelper::addAllProvidersTo($faker);

    return [
        'quantity' => rand(1, 20),
    ];
});
