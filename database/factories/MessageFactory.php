<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Message;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(Message::class, function (Faker $faker) {
    return [
        'external_id' => $faker->uuid,
        'content' => $faker->sentence(rand(1, 10)),
        'user_id' => rand(0, 1) ? User::select('id')->inRandomOrder()->first()->getKey() : null,
    ];
});
