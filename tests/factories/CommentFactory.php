<?php

use Bakery\Tests\Models\User;
use Bakery\Tests\Models\Article;
use Bakery\Tests\Models\Comment;

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
