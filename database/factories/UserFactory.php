<?php

use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(\App\Country::class, function (Faker $faker) {
    return [
        'slug' => $faker->unique()->numberBetween(0,20),
        'name' => 'روسیه',
        'price' => round(rand(1000,20000),-3)
    ];
});
