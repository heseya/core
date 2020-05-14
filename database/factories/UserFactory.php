<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\User;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => '$argon2d$v=19$m=1024,t=1,p=1$emFxMUBXU1g$glYLVr9V7GcJL6MUVbUVYjLnwpb7Wzm7F18GgvEOQ3U', // secret
        'remember_token' => Str::random(10),
    ];
});
