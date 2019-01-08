<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\IntegrationTest;
use Bakery\Tests\Fixtures\Models\Role;
use Bakery\Tests\Fixtures\Models\User;
use Bakery\Tests\Fixtures\Models\Phone;
use Bakery\Tests\Fixtures\Models\Article;

class AuthorizationTest extends IntegrationTest
{
    public function setUp()
    {
        parent::setUp();

        $this->authenticate();
    }

    /** @test */
    public function it_cant_create_and_save_has_one_if_not_authorized()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.phone.create'] = false;

        $this->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'phone' => [
                    'number' => '+31612345678',
                ],
            ],
        ]);

        unset($_SERVER['graphql.phone.create']);

        $user = User::first();
        $this->assertNull($user->phone);
    }

    /** @test */
    public function it_cant_save_has_one_if_not_authorized()
    {
        $user = factory(User::class)->create();
        $phone = factory(Phone::class)->create();

        $_SERVER['graphql.user.savePhone'] = false;

        $this->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'phoneId' => $phone->id,
            ],
        ]);

        unset($_SERVER['graphql.user.savePhone']);

        $user = User::first();
        $this->assertFalse($user->phone->is($phone));
    }

    /** @test */
    public function it_cant_create_and_add_has_many_if_not_authorized()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.article.create'] = false;

        $this->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'articles' => [
                    [
                        'title' => 'Hello world!',
                        'slug' => 'hello-world',
                    ],
                ],
            ],
        ]);

        unset($_SERVER['graphql.article.create']);

        $user = User::first();
        $this->assertTrue($user->articles->isEmpty());
    }

    /** @test */
    public function it_cant_add_has_many_if_not_authorized()
    {
        $user = factory(User::class)->create();
        $article = factory(Article::class)->create();

        $_SERVER['graphql.user.addArticle'] = false;

        $this->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'articleIds' => [
                    $article->id,
                ],
            ],
        ]);

        unset($_SERVER['graphql.user.addArticle']);

        $user = User::first();
        $this->assertTrue($user->articles->isEmpty());
    }

    /** @test */
    public function it_cant_create_and_attach_belongs_to_many_if_not_authorized()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.role.create'] = false;

        $this->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'roles' => [
                    ['name' => 'Administrator', 'pivot' => []],
                ],
            ],
        ]);

        unset($_SERVER['graphql.role.create']);

        $user = User::first();
        $this->assertTrue($user->roles->isEmpty());
    }

    /** @test */
    public function it_cant_attach_belongs_to_many_if_not_authorized()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();

        $_SERVER['graphql.user.attachRole'] = false;

        $this->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'roleIds' => [
                    ['id' => $role->id, 'pivot' => []],
                ],
            ],
        ]);

        unset($_SERVER['graphql.user.attachRole']);

        $user = User::first();
        $this->assertTrue($user->roles->isEmpty());
    }

    /** @test */
    public function it_cant_detach_belongs_to_many_if_not_authorized()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $user->roles()->attach($role);

        $_SERVER['graphql.user.detachRole'] = false;

        $this->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'roleIds' => [],
            ],
        ]);

        unset($_SERVER['graphql.user.detachRole']);

        $user = User::first();
        $this->assertTrue($user->roles->isNotEmpty());
    }
}
