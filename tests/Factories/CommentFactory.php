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
$factory->define(Bakery\Tests\Fixtures\Models\Comment::class, function (Faker\Generator $faker) {
    return [
        'commentable_id' => factory(Bakery\Tests\Fixtures\Models\Article::class),
        'commentable_type' => Bakery\Tests\Fixtures\Models\Article::class,
        'author_id' => factory(Bakery\Tests\Fixtures\Models\User::class),
        'body' => $faker->sentence,
    ];
});
