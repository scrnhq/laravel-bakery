<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;
use Bakery\Tests\Fixtures\Models\Tag;
use Bakery\Tests\Fixtures\Models\Role;
use Bakery\Tests\Fixtures\Models\User;
use Bakery\Tests\Fixtures\Models\Phone;
use Bakery\Tests\Fixtures\Models\Article;
use Bakery\Tests\Fixtures\Models\Comment;

class CreateMutationTest extends IntegrationTest
{
    public function setUp()
    {
        parent::setUp();

        $this->authenticate();
    }

    /** @test */
    public function it_can_create_models()
    {
        $this->graphql('mutation($input: CreateUserInput!) { createUser(input: $input) { id } }', [
            'input' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'secret',
            ],
        ]);

        $user = User::first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('secret', $user->password);
    }

    /** @test */
    public function it_cant_create_models_if_the_model_has_no_policy()
    {
        Gate::policy(User::class, null);

        $this->withExceptionHandling()->graphql('mutation($input: CreateUserInput!) { createUser(input: $input) { id } }', [
            'input' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'secret',
            ],
        ]);

        $user = User::first();
        $this->assertNull($user);
    }

    /** @test */
    public function it_cant_create_model_if_not_authorized()
    {
        $_SERVER['graphql.user.creatable'] = false;

        $this->withExceptionHandling()->graphql('mutation($input: CreateUserInput!) { createUser(input: $input) { id } }', [
            'input' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'secret',
            ],
        ]);

        unset($_SERVER['graphql.user.creatable']);

        $user = User::first();
        $this->assertNull($user);
    }

    /** @test */
    public function it_can_create_a_has_one_relationship()
    {
        $this->graphql('mutation($input: CreateUserInput!) { createUser(input: $input) { id } }', [
            'input' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'secret',
                'phone' => [
                    'number' => '+31612345678',
                ],
            ],
        ]);

        $phone = Phone::first();
        $this->assertEquals('+31612345678', $phone->number);
        $this->assertEquals('1', $phone->user_id);

        $user = User::first();
        $this->assertEquals('john@example.com', $user->email);
    }

    /** @test */
    public function it_can_save_a_has_one_relationship()
    {
        $phone = factory(Phone::class)->create();

        $this->graphql('mutation($input: CreateUserInput!) { createUser(input: $input) { id } }', [
            'input' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'secret',
                'phoneId' => $phone->id,
            ],
        ]);

        $user = User::first();
        $this->assertEquals($phone->user_id, $user->id);
    }

    /** @test */
    public function it_can_create_with_null_has_one_relationship()
    {
        $this->graphql('mutation($input: CreateUserInput!) { createUser(input: $input) { id } }', [
            'input' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'secret',
                'phoneId' => null,
            ],
        ]);

        $user = User::first();
        $this->assertEquals('john@example.com', $user->email);

        $phone = Phone::first();
        $this->assertNull($phone);
    }

    /** @test */
    public function it_can_create_a_belongs_to_relationship()
    {
        $this->withExceptionHandling()->graphql('mutation($input: CreatePhoneInput!) { createPhone(input: $input) { id } }', [
            'input' => [
                'number' => '+31612345678',
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'password' => 'secret',
                ],
            ],
        ]);

        $phone = Phone::first();
        $this->assertEquals('+31612345678', $phone->number);
        $this->assertEquals('1', $phone->user_id);
        $user = User::first();
        $this->assertEquals('john@example.com', $user->email);
    }

    /** @test */
    public function it_can_save_a_belongs_to_relationship()
    {
        $user = factory(User::class)->create();

        $this->graphql('mutation($input: CreatePhoneInput!) { createPhone(input: $input) { id } }', [
            'input' => [
                'number' => '+31612345678',
                'userId' => $user->id,
            ],
        ]);

        $phone = Phone::first();
        $this->assertEquals('+31612345678', $phone->number);
        $this->assertEquals('1', $phone->user_id);
    }

    /** @test */
    public function it_can_save_a_null_belongs_to_relationship()
    {
        $this->graphql('mutation($input: CreateArticleInput!) { createArticle(input: $input) { id } }', [
            'input' => [
                'title' => 'Hello world!',
                'slug' => 'hello-world',
                'userId' => null,
            ],
        ]);

        $article = Article::first();
        $this->assertNull($article->user_id);
    }

    /** @test */
    public function it_can_create_a_has_many_relationship()
    {
        $this->graphql('mutation($input: CreateUserInput!) { createUser(input: $input) { id } }', [
            'input' => [
                'email' => 'john@example.com',
                'name' => 'John Doe',
                'password' => 'secret',
                'articles' => [
                    [
                        'title' => 'Hello world!',
                        'slug' => 'hello-world',
                    ],
                ],
            ],
        ]);

        $article = Article::first();
        $this->assertEquals('Hello world!', $article->title);
        $this->assertEquals('1', $article->user_id);
    }

    /** @test */
    public function it_can_save_a_has_many_relationship()
    {
        $article = factory(Article::class)->create();

        $this->graphql('mutation($input: CreateUserInput!) { createUser(input: $input) { id } }', [
            'input' => [
                'email' => 'john@example.com',
                'name' => 'John Doe',
                'password' => 'secret',
                'articleIds' => [
                    $article->id,
                ],
            ],
        ]);

        $article = Article::first();
        $this->assertEquals('john@example.com', $article->user->email);
    }

    /** @test */
    public function it_can_save_an_empty_has_many_relationship()
    {
        $this->graphql('mutation($input: CreateUserInput!) { createUser(input: $input) { id } }', [
            'input' => [
                'email' => 'john@example.com',
                'name' => 'John Doe',
                'password' => 'secret',
                'articleIds' => [],
            ],
        ]);

        $user = User::first();
        $this->assertEmpty($user->articles);
    }

    /** @test */
    public function it_can_create_belongs_to_many_with_pivot_data()
    {
        $this->graphql('mutation($input: CreateRoleInput!) { createRole(input: $input) { id } }', [
            'input' => [
                'name' => 'Administrator',
                'users' => [
                    [
                        'email' => 'john@example.com',
                        'name' => 'John Doe',
                        'password' => 'secret',
                        'pivot' => [
                            'admin' => true,
                        ],
                    ],
                ],
            ],
        ]);

        $role = Role::first();
        $this->assertEquals(true, $role->users[0]->pivot->admin);

        $user = User::first();
        $this->assertEquals('john@example.com', $user->email);
    }

    /** @test */
    public function it_can_attach_belongs_to_many_with_pivot_data()
    {
        $user = factory(User::class)->create();

        $this->graphql('mutation($input: CreateRoleInput!) { createRole(input: $input) { id } }', [
            'input' => [
                'name' => 'Administrator',
                'userIds' => [
                    [
                        'id' => $user->id,
                        'pivot' => [
                            'admin' => true,
                        ],
                    ],
                ],
            ],
        ]);

        $role = Role::first();
        $this->assertEquals(true, $role->users[0]->pivot->admin);
    }

    /** @test */
    public function it_can_create_belongs_to_many_with_pivot_data_with_custom_pivot_accessor()
    {
        $user = factory(User::class)->create();

        $_SERVER['eloquent.role.users.pivot'] = 'customPivot';

        $this->graphql('mutation($input: CreateRoleInput!) { createRole(input: $input) { id } }', [
            'input' => [
                'name' => 'Administrator',
                'userIds' => [
                    [
                        'id' => $user->id,
                        'customPivot' => [
                            'admin' => true,
                        ],
                    ],
                ],
            ],
        ]);

        $role = Role::first();
        $this->assertEquals(true, $role->users[0]->customPivot->admin);

        unset($_SERVER['eloquent.role.users.pivot']);
    }

    /** @test */
    public function it_can_create_a_morph_to_relationship()
    {
        $user = factory(User::class)->create();

        $this->graphql('mutation($input: CreateCommentInput!) { createComment(input: $input) { id } }', [
            'input' => [
                'authorId' => $user->id,
                'body' => 'Great post!',
                'commentable' => [
                    'article' => [
                        'title' => 'Hello world!',
                        'slug' => 'hello-world',
                    ],
                ],
            ],
        ]);

        $comment = Comment::first();
        $this->assertEquals('1', $comment->commentable_id);
        $article = Article::first();
        $this->assertEquals('Hello world!', $article->title);
    }

    /** @test */
    public function it_can_attach_a_morph_to_relationship()
    {
        $user = factory(User::class)->create();
        $article = factory(Article::class)->create();

        $this->graphql('mutation($input: CreateCommentInput!) { createComment(input: $input) { id } }', [
            'input' => [
                'authorId' => $user->id,
                'body' => 'Great post!',
                'commentableId' => [
                    'article' => $article->id,
                ],
            ],
        ]);

        $comment = Comment::first();
        $this->assertEquals('1', $comment->commentable_id);
    }

    /** @test */
    public function it_can_create_a_morph_many_relationship()
    {
        $user = factory(User::class)->create();

        $this->graphql('mutation($input: CreateArticleInput!) { createArticle(input: $input) { id } }', [
            'input' => [
                'userId' => $user->id,
                'title' => 'Hello world!',
                'slug' => 'hello-world',
                'comments' => [
                    [
                        'body' => 'Great post!',
                        'authorId' => $user->id,
                    ],
                ],
            ],
        ]);

        $comment = Comment::first();
        $this->assertEquals('Great post!', $comment->body);
        $this->assertEquals('1', $comment->commentable_id);
    }

    /** @test */
    public function it_can_attach_a_morph_many_relation()
    {
        $user = factory(User::class)->create();
        $comment = factory(Comment::class)->create();

        $this->graphql('mutation($input: CreateArticleInput!) { createArticle(input: $input) { id } }', [
            'input' => [
                'userId' => $user->id,
                'title' => 'Hello world!',
                'slug' => 'hello-world',
                'commentIds' => [
                    $comment->id,
                ],
            ],
        ]);

        $article = Article::all()->last();
        $this->assertEquals('Hello world!', $article->title);
        $this->assertTrue($article->comments->contains($comment));
    }

    /** @test */
    public function it_can_create_a_morphed_by_many_relationship()
    {
        $user = factory(User::class)->create();

        $this->graphql('mutation($input: CreateTagInput!) { createTag(input: $input) { id } }', [
            'input' => [
                'name' => 'News',
                'articles' => [
                    [
                        'title' => 'Hello world!',
                        'slug' => 'hello-world',
                        'userId' => $user->id,
                    ],
                ],
            ],
        ]);

        $tag = Tag::first();
        $this->assertEquals('Hello world!', $tag->articles->first()->title);
    }

    /** @test */
    public function it_can_attach_a_morphed_by_many_relationship()
    {
        $article = factory(Article::class)->create();

        $this->graphql('mutation($input: CreateTagInput!) { createTag(input: $input) { id } }', [
            'input' => [
                'name' => 'News',
                'articleIds' => [
                    $article->id,
                ],
            ],
        ]);

        $tag = Tag::first();
        $this->assertTrue($tag->articles->contains($article));
    }

    /** @test */
    public function it_can_create_nested_relationships()
    {
        $this->graphql('mutation($input: CreateUserInput!) { createUser(input: $input) { id } }', [
            'input' => [
                'email' => 'john@example.com',
                'name' => 'John Doe',
                'password' => 'secret',
                'articles' => [
                    [
                        'title' => 'Hello world!',
                        'slug' => 'hello-world',
                        'comments' => [
                            [
                                'body' => 'Great post!',
                                'author' => [
                                    'name' => 'Jane Doe',
                                    'email' => 'jane@example.com',
                                    'password' => 'secret',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $user = User::first();
        $article = Article::first();
        $comment = Comment::first();
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('1', $article->user_id);
        $this->assertEquals('1', $comment->commentable_id);
    }

    /** @test */
    public function it_throws_exception_when_passing_multiple_keys_to_morph_to_relationship()
    {
        $this->withExceptionHandling();

        $response = $this->graphql('mutation($input: CreateCommentInput!) { createComment(input: $input) { id } }', [
            'input' => [
                'body' => 'Great post!',
                'commentableId' => [
                    'article' => '1',
                    'user' => '1',
                ],
            ],
        ]);

        $response->assertJsonFragment(['message' => 'There must be only one key with polymorphic input. 2 given for relation commentable.']);
    }
}
