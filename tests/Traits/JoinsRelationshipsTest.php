<?php

namespace Bakery\Tests\Traits;

use Bakery\Tests\TestCase;
use Bakery\Traits\JoinsRelationships;
use Bakery\Tests\Fixtures\Models\Role;
use Bakery\Tests\Fixtures\Models\User;
use Bakery\Tests\Fixtures\Models\Article;

class JoinsRelationshipsTest extends TestCase
{
    use JoinsRelationships;

    /** @test */
    public function it_can_join_a_belongs_to_relation()
    {
        $article = resolve(Article::class);
        $articles = Article::query();

        $this->joinRelation($articles, $article->user());

        $this->assertEquals('select `articles`.* from `articles` inner join `users` on `users`.`id` = `articles`.`user_id`', $articles->toSql());
    }

    /** @test */
    public function it_can_join_a_belongs_to_many_relation()
    {
        $role = resolve(Role::class);
        $roles = Role::query();

        $this->joinRelation($roles, $role->users());

        $this->assertEquals('select `roles`.* from `roles` inner join `role_user` on `roles`.`id` = `role_user`.`role_id` inner join `users` on `role_user`.`user_id` = `users`.`id`', $roles->toSql());
    }

    /** @test */
    public function it_can_join_a_has_many_relation()
    {
        $user = resolve(User::class);
        $users = User::query();

        $this->joinRelation($users, $user->articles());

        $this->assertEquals('select `users`.* from `users` inner join `articles` on `articles`.`user_id` = `users`.`id`', $users->toSql());
    }
}
