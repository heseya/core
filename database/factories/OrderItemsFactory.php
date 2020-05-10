<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\OrderItem;
use Faker\Generator as Faker;

$factory->define(OrderItem::class, function (Faker $faker) {
    return [
        'quantity' => rand(1, 4),
        'price' => rand(100, 200),
        'product_id' => rand(1, 100),
    ];
});