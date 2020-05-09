<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Media;
use Faker\Generator as Faker;

$factory->define(Media::class, function (Faker $faker) {
    return [
        'type' => Media::PHOTO,
        'url' => 'https://loremflickr.com/850/850/smartphone?lock=' . rand(0, 9999999),
    ];
});
