<?php

use Bakery\Tests\Stubs\Models\User;
use Bakery\Tests\Stubs\Models\Article;
use Bakery\Tests\Stubs\Models\Comment;

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
$factory->define(Comment::class, function (Faker\Generator $faker) {
    return [
        'body' => $faker->sentence,
        'article_id' => function () {
            return factory(Article::class)->create()->id;
        },
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
    ];
});
