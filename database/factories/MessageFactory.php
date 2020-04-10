<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Message;
use Faker\Generator as Faker;

$factory->define(Message::class, function (Faker $faker) {
    return [
        'external_id' => $faker->uuid,
        'content' => $faker->realText(rand(20, 200)),
        'user_id' => rand(0, 1) ? null : 1,
    ];
});
