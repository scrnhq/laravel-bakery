<?php

use Bakery\Tests\Models\User;
use Bakery\Tests\Models\Article;

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
/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Article::class, function (Faker\Generator $faker) {
    return [
        'title' => $faker->sentence,
        'slug' => $faker->word,
        'content' => $faker->realText(),
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
    ];
});
