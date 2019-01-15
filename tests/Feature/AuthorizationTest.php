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
    public function it_cant_read_a_field_if_not_authorize_and_returns_null()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.user.canSeePassword'] = false;

        $query = '
            query($id: ID!) {
                user(id: $id) {
                    id
                    password
                }
            }
        ';

        $response = $this->withExceptionHandling()->graphql($query, ['id' => $user->id]);
        $response->assertJsonFragment(['user' => null]);

        unset($_SERVER['graphql.user.canSeePassword']);
    }

    /** @test */
    public function it_cant_read_nullable_field_if_not_authorized_and_returns_null_for_field()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.user.viewRestricted'] = false;

        $query = '
            query($id: ID!) {
                user(id: $id) {
                    id
                    restricted
                }
            }
        ';

        $response = $this->graphql($query, ['id' => $user->id]);
        $response->assertJsonFragment(['restricted' => null]);

        unset($_SERVER['graphql.user.viewRestricted']);
    }

    /** @test */
    public function it_can_read_field_if_authorized()
    {
        $user = factory(User::class)->create();

        $query = '
            query($id: ID!) {
                user(id: $id) {
                    id
                    restricted
                }
            }
        ';

        $response = $this->graphql($query, ['id' => $user->id]);
        $response->assertJsonFragment(['restricted' => 'Yes']);
    }

    /** @test */
    public function it_cant_read_a_relation_if_not_authorized_and_returns_null()
    {
        $article = factory(Article::class)->create();

        $_SERVER['graphql.user.canSeeArticles'] = false;

        $query = '
            query($id: ID!) {
                user(id: $id) {
                    id
                    articles {
                        id
                        title
                    }
                }
            }
        ';

        $response = $this->withExceptionHandling()->graphql($query, ['id' => $article->user->id]);
        $response->assertJsonFragment(['user' => null]);

        unset($_SERVER['graphql.user.canSeeArticles']);
    }

    /** @test */
    public function it_cant_read_relation_ids_if_not_authorized_and_returns_null()
    {
        $article = factory(Article::class)->create();

        $_SERVER['graphql.user.canSeeArticles'] = false;

        $query = '
            query($id: ID!) {
                user(id: $id) {
                    id
                    articles {
                        id
                        title
                    }
                }
            }
        ';

        $response = $this->withExceptionHandling()->graphql($query, ['id' => $article->user->id]);
        $response->assertJsonFragment(['user' => null]);

        unset($_SERVER['graphql.user.canSeeArticles']);
    }

    /** @test */
    public function it_can_read_a_relation_if_authorized()
    {
        $article = factory(Article::class)->create();

        $query = '
            query($id: ID!) {
                user(id: $id) {
                    id
                    articles {
                        id
                        title
                    }
                }
            }
        ';

        $response = $this->graphql($query, ['id' => $article->user->id]);
        $response->assertJsonStructure(['data' => ['user' => ['articles']]]);
        $response->assertJsonFragment(['title' => $article->title]);
    }

    /** @test */
    public function it_can_read_relation_ids_if_authorized()
    {
        $article = factory(Article::class)->create();

        $query = '
            query($id: ID!) {
                user(id: $id) {
                    id
                    articleIds
                }
            }
        ';

        $response = $this->graphql($query, ['id' => $article->user->id]);
        $response->assertJsonStructure(['data' => ['user' => ['articleIds']]]);
        $response->assertJsonFragment(['articleIds' => [$article->id]]);
    }

    /** @test */
    public function it_cant_read_a_field_if_not_authorized_by_policy()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.user.viewRestricted'] = false;

        $query = '
            query($id: ID!) {
                user(id: $id) {
                    id
                    restricted
                }
            }
        ';

        $response = $this->withExceptionHandling()->graphql($query, ['id' => $user->id]);
        $response->assertJsonFragment(['restricted' => null]);

        unset($_SERVER['graphql.user.viewRestricted']);
    }

    /** @test */
    public function it_can_read_a_field_if_authorized_by_policy()
    {
        $user = factory(User::class)->create();

        $query = '
            query($id: ID!) {
                user(id: $id) {
                    id
                    restricted
                }
            }
        ';

        $response = $this->withExceptionHandling()->graphql($query, ['id' => $user->id]);
        $response->assertJsonFragment(['restricted' => 'Yes']);
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
    public function it_cant_create_and_add_has_one_if_not_authorized_to_save()
    {
        $user = factory(User::class)->create();

        $_SERVER['graphql.user.addPhone'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'phone' => [
                    'number' => '+31612345678',
                ],
            ],
        ]);

        unset($_SERVER['graphql.user.addPhone']);

        $user = User::first();
        $this->assertNull($user->phone);
    }

    /** @test */
    public function it_cant_save_has_one_if_not_authorized()
    {
        $user = factory(User::class)->create();
        $phone = factory(Phone::class)->create();

        $_SERVER['graphql.user.addPhone'] = false;

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'phoneId' => $phone->id,
            ],
        ]);

        unset($_SERVER['graphql.user.addPhone']);

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
