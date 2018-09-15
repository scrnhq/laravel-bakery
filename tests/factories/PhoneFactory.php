<?php

use Bakery\Tests\Stubs\Models\User;
use Bakery\Tests\Stubs\Models\Phone;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/
/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Phone::class, function (Faker\Generator $faker) {
    return [
        'number' => $faker->phoneNumber,
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
    ];
});
