<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\ProductSchemaItem;
use Faker\Generator as Faker;

$factory->define(ProductSchemaItem::class, function (Faker $faker) {
    return [
        'extra_price' => rand(0, 100),
    ];
});
