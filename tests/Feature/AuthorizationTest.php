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
    public function it_cant_create_and_save_has_one_if_not_authorized_to_create()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.phone.creatable'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'phone' => [
                    'number' => '+31612345678',
                ],
            ],
        ]);

        unset($_SERVER['graphql.phone.creatable']);

        $user = User::first();
        $this->assertNull($user->phone);
    }

    /** @test */
    public function it_cant_create_and_save_has_one_if_not_authorized_to_save()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.user.savePhone'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'phone' => [
                    'number' => '+31612345678',
                ],
            ],
        ]);

        unset($_SERVER['graphql.user.savePhone']);

        $user = User::first();
        $this->assertNull($user->phone);
    }

    /** @test */
    public function it_cant_save_has_one_if_not_authorized()
    {
        $user = factory(User::class)->create();
        $phone = factory(Phone::class)->create();

        $_SERVER['graphql.user.savePhone'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'phoneId' => $phone->id,
            ],
        ]);

        unset($_SERVER['graphql.user.savePhone']);

        $user = User::first();
        $this->assertNull($user->phone);
    }

    /** @test */
    public function it_cant_create_and_add_has_many_if_not_authorized_to_create()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.article.creatable'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
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

        unset($_SERVER['graphql.article.creatable']);

        $user = User::first();
        $this->assertTrue($user->articles->isEmpty());
    }

    /** @test */
    public function it_cant_create_and_add_has_many_if_not_authorized_to_add()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.user.addArticle'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
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

        unset($_SERVER['graphql.user.addArticle']);

        $user = User::first();
        $this->assertTrue($user->articles->isEmpty());
    }

    /** @test */
    public function it_cant_add_has_many_if_not_authorized()
    {
        $user = factory(User::class)->create();
        $article = factory(Article::class)->create();

        $_SERVER['graphql.user.addArticle'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
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
    public function it_cant_create_and_add_belongs_to_if_not_authorized_to_create()
    {
        $article = factory(Article::class)->create(['user_id' => null]);

        $_SERVER['graphql.user.creatable'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateArticleInput!) { updateArticle(id: $id, input: $input) { id } }', [
            'id' => $article->id,
            'input' => [
                'user' => [
                    'email' => 'john@example.com',
                    'name' => 'John Doe',
                    'password' => 'secret',
                ],
            ],
        ]);

        unset($_SERVER['graphql.user.creatable']);

        $article = Article::first();
        $this->assertNull($article->user);
    }

    /** @test */
    public function it_cant_create_and_add_belongs_to_if_not_authorized_to_add()
    {
        $article = factory(Article::class)->create(['user_id' => null]);

        $_SERVER['graphql.user.addArticle'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateArticleInput!) { updateArticle(id: $id, input: $input) { id } }', [
            'id' => $article->id,
            'input' => [
                'user' => [
                    'email' => 'john@example.com',
                    'name' => 'John Doe',
                    'password' => 'secret',
                ],
            ],
        ]);

        unset($_SERVER['graphql.user.addArticle']);

        $article = Article::first();
        $this->assertNull($article->user);
    }

    /** @test */
    public function it_cant_add_belongs_to_if_not_authorized()
    {
        $user = factory(User::class)->create();
        $article = factory(Article::class)->create(['user_id' => null]);

        $_SERVER['graphql.user.addArticle'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateArticleInput!) { updateArticle(id: $id, input: $input) { id } }', [
            'id' => $article->id,
            'input' => [
                'userId' => $user->id,
            ],
        ]);

        unset($_SERVER['graphql.user.addArticle']);

        $article = Article::first();
        $this->assertNull($article->user);
    }

    /** @test */
    public function it_cant_create_and_attach_belongs_to_many_if_not_authorized_to_create()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.role.creatable'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'roles' => [
                    ['name' => 'Administrator', 'pivot' => []],
                ],
            ],
        ]);

        unset($_SERVER['graphql.role.creatable']);

        $user = User::first();
        $this->assertTrue($user->roles->isEmpty());
    }

    /** @test */
    public function it_cant_create_and_attach_belongs_to_many_if_not_authorized_to_attach()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.user.attachRole'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'roles' => [
                    ['name' => 'Administrator', 'pivot' => []],
                ],
            ],
        ]);

        unset($_SERVER['graphql.user.attachRole']);

        $user = User::first();
        $this->assertTrue($user->roles->isEmpty());
    }

    /** @test */
    public function it_cant_create_and_attach_belongs_to_many_if_not_authorized_to_detach()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.user.attachRole'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'roles' => [
                    ['name' => 'Administrator', 'pivot' => []],
                ],
            ],
        ]);

        unset($_SERVER['graphql.user.attachRole']);

        $user = User::first();
        $this->assertTrue($user->roles->isEmpty());
    }

    /** @test */
    public function it_cant_attach_belongs_to_many_if_not_authorized()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();

        $_SERVER['graphql.user.attachRole'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
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

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'roleIds' => [],
            ],
        ]);

        unset($_SERVER['graphql.user.detachRole']);

        $user = User::first();
        $this->assertTrue($user->roles->isNotEmpty());
    }

    /** @test */
    public function it_cant_sync_belongs_to_many_if_not_authorized_to_detach_existing()
    {
        $user = factory(User::class)->create();
        $user->roles()->attach(factory(Role::class)->create());

        $role = factory(Role::class)->create();

        $_SERVER['graphql.user.detachRole'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'roleIds' => [
                    ['id' => $role->id, 'pivot' => []],
                ],
            ],
        ]);

        unset($_SERVER['graphql.user.detachRole']);

        $user = User::first();
        $this->assertTrue($user->roles->isNotEmpty());
        $this->assertFalse($user->roles->contains($role));
    }
}
