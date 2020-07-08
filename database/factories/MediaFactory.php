<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Media;
use Faker\Generator as Faker;

$factory->define(Media::class, function (Faker $faker) {
    return [
        'type' => Media::PHOTO,
        'url' => 'https://picsum.photos/id/' . rand(0, 1084) . '/800',
    ];
});
