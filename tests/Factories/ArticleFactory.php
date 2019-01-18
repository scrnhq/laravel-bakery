<?php

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
$factory->define(Bakery\Tests\Fixtures\Models\Article::class, function (Faker\Generator $faker) {
    return [
        'user_id' => factory(Bakery\Tests\Fixtures\Models\User::class),
        'title' => $faker->word,
        'slug' => $faker->unique()->slug,
        'content' => $faker->text,
    ];
});
