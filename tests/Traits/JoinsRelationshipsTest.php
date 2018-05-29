<?php

namespace Bakery\Tests\Traits;

use Bakery\Tests\Stubs;
use Bakery\Tests\Models\User;
use Bakery\Tests\Models\Article;

use Bakery\Tests\TestCase;
use Bakery\Tests\WithDatabase;
use Bakery\Traits\JoinsRelationships;

class JoinsRelationshipsTest extends TestCase
{
    use JoinsRelationships;

    /** @test */
    public function it_can_join_a_belongs_to_relation()
    {
        $articles = Article::query();

        $this->joinRelation($articles, 'user');

        $this->assertEquals('select `articles`.* from `articles` inner join `users` on `users`.`id` = `articles`.`user_id`', $articles->toSql());
    }

    /** @test */
    public function it_can_join_a_belongs_to_many_relation()
    {
        $users = User::query();

        $this->joinRelation($users, 'roles');

        $this->assertEquals('select `users`.* from `users` inner join `role_user` on `users`.`id` = `role_user`.`user_id` inner join `roles` on `role_user`.`role_id` = `roles`.`id`', $users->toSql());
    }

    /** @test */
    public function it_can_join_a_has_many_relation()
    {
        $users = User::query();

        $this->joinRelation($users, 'articles');

        $this->assertEquals('select `users`.* from `users` inner join `articles` on `articles`.`user_id` = `users`.`id`', $users->toSql());
    }
}
