<?php

namespace Bakery\Tests\Traits;

use Bakery\Tests\Stubs;
use Bakery\Tests\Stubs\Post;
use Bakery\Tests\Stubs\User;
use Bakery\Tests\TestCase;
use Bakery\Tests\WithDatabase;
use Bakery\Traits\JoinsRelationships;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Query\JoinClause;

class JoinsRelationshipsTest extends TestCase
{
    use JoinsRelationships;

    /** @test */
    public function it_can_join_a_belongs_to_relation()
    {
        $posts = Post::query();

        $this->joinRelation($posts, 'user');

        $this->assertEquals('select `posts`.* from `posts` inner join `users` on `users`.`id` = `posts`.`user_id`', $posts->toSql());
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

        $this->joinRelation($users, 'posts');

        $this->assertEquals('select `users`.* from `users` inner join `posts` on `posts`.`user_id` = `users`.`id`', $users->toSql());
    }
}
