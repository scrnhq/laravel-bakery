<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\Models;
use Bakery\Tests\Models\Article;
use Bakery\Tests\Models\Comment;
use Bakery\Tests\FeatureTestCase;

class CreateMutationTest extends FeatureTestCase
{
    /** @test */
    public function it_does_not_allow_creating_entity_as_guest()
    {
        $this->withExceptionHandling();

        $query = '
            mutation {
                createArticle(input: {
                    title: "Hello world!"
                    slug: "hello-world"
                    content: "Lorem ispum"
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonMissing(['data']);
        $this->assertDatabaseMissing('articles', ['title' => 'Hello world!']);
    }

    /** @test */
    public function it_does_not_allow_creating_entity_as_user_when_there_is_no_policy()
    {
        $this->withExceptionHandling();
        $this->actingAs(factory(Models\User::class)->create());

        $query = '
            mutation {
                createCategory(input: {
                    name: "some-category"
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonMissing(['data']);
        $this->assertDatabaseMissing('categories', ['name' => 'some-category']);
    }

    /** @test */
    public function it_does_allow_creating_entity_as_user_when_it_is_allowed_by_policy()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createArticle(input: {
                    title: "Hello world!"
                    slug: "hello-world"
                    content: "Lorem ipsum"
                    userId: 1,
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('articles', ['title' => 'Hello world!']);
    }

    /** @test */
    public function it_lets_you_create_a_has_one_relationship()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com",
                    name: "Jane Doe",
                    password: "secret",
                    phone: { number: "+31612345678" },
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('users', ['email' => 'jane.doe@example.com']);
        $this->assertDatabaseHas('phones', ['number' => '+31612345678', 'user_id' => '2']);
    }

    /** @test */
    public function it_lets_you_save_a_has_one_relationship()
    {
        $phone = factory(Models\Phone::class)->create();

        $this->actingAs($phone->user);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com",
                    name: "Jane Doe",
                    password: "secret",
                    phoneId: "'.$phone->id.'",
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('users', ['email' => 'jane.doe@example.com']);
        $this->assertDatabaseHas('phones', ['user_id' => '2']);
    }

    /** @test */
    public function it_lets_you_set_a_has_one_relationship_to_null()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com",
                    name: "Jane Doe",
                    password: "secret",
                    phoneId: null, 
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('users', ['email' => 'jane.doe@example.com']);
    }

    /** @test */
    public function it_lets_you_create_a_belongs_to_relationship()
    {
        $user = factory(Models\User::class)->create();

        $this->actingAs($user);

        $query = '
            mutation {
                createPhone(input: {
                    number: "+31612345678",
                    user: {
                        name: "Jane Doe",
                        email: "jane.doe@example.com",
                        password: "secret",
                    }
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('phones', ['number' => '+31612345678', 'user_id' => '2']);
        $this->assertDatabaseHas('users', ['name' => 'Jane Doe']);
    }

    /** @test */
    public function it_lets_you_to_assign_a_belongs_to_relationship()
    {
        $user = factory(Models\User::class)->create();

        $this->actingAs($user);

        $article = factory(Models\Article::class)->create(['user_id' => 1]);

        $query = '
            mutation {
                createComment(input: {
                    body: "Cool story bro",
                    userId: '.$user->id.'
                    articleId: '.$article->id.'
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('comments', ['id' => '1', 'article_id' => $article->id, 'user_id' => $user->id]);
    }

    /** @test */
    public function it_lets_you_insert_a_has_many_relationship()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createArticle(input: {
                    title: "Hello World"
                    slug: "hello-world"
                    content: "Lorem ipsum"
                    userId: 1
                    comments: [
                        { body: "First!", userId: 1 }
                        { body: "Great post!", userId: 1 }
                    ]
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('articles', ['title' => 'Hello World', 'user_id' => '1']);
        $this->assertDatabaseHas('comments', ['body' => 'First!', 'article_id' => '1']);
        $this->assertDatabaseHas('comments', ['body' => 'Great post!', 'article_id' => '1']);
    }

    /** @test */
    public function it_lets_you_do_deep_nested_create_mutations()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com"
                    name: "Jane Doe"
                    password: "secret"
                    articles: [{
                        title: "Hello World!"
                        slug: "hello-world" 
                        content: "Lorem ipsum"
                        comments: [
                            { body: "First!", userId: 1 }
                            { body: "Great post!", userId: 1 }
                        ]
                    }]
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('users', ['email' => 'jane.doe@example.com', 'name' => 'Jane Doe']);
        $this->assertDatabaseHas('articles', ['title' => 'Hello World!', 'user_id' => '2']);
        $this->assertDatabaseHas('comments', ['body' => 'First!', 'article_id' => '1']);
        $this->assertDatabaseHas('comments', ['body' => 'Great post!', 'article_id' => '1']);
    }

    /** @test */
    public function it_lets_you_reset_a_belongs_to_relationship()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createArticle(input: {
                    userId: "'.$user->id.'",
                    categoryId: null,
                    title: "Hello World!"
                    slug: "hello-world" 
                    content: "Lorem ipsum"
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('articles', ['title' => 'Hello World!', 'category_id' => null]);
    }

    /** @test */
    public function it_lets_you_attach_pivot_data()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createRole(input: {
                    name: "administrator"
                    userIds: [
                        { id: "'.$user->id.'", pivot: { comment: "foobar" } }
                    ],
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'comment' => 'foobar',
        ]);
    }

    /** @test */
    public function it_lets_you_attach_pivot_data_with_custom_pivot()
    {
        $user = factory(Models\User::class)->create();
        $role = factory(Models\Role::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com",
                    name: "Jane Doe",
                    password: "secret",
                    customRoleIds: [
                        { id: "'.$role->id.'", customPivot: { comment: "foobar" } }
                    ],
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('role_user', [
            'user_id' => '2',
            'role_id' => $role->id,
            'comment' => 'foobar',
        ]);
    }

    /** @test */
    public function it_lets_you_set_pivot_data_while_creating_relation()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createRole(input: {
                    name: "administrator",
                    users: [
                        {
                            email: "jane.doe@example.com"
                            name: "Jane Doe"
                            password: "secret"
                            pivot: {
                                comment: "foobar"
                            }
                        }
                    ],
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('roles', ['id' => '1', 'name' => 'administrator']);
        $this->assertDatabaseHas('users', ['id' => '2', 'email' => 'jane.doe@example.com']);
        $this->assertDatabaseHas('role_user', [
            'user_id' => '2',
            'role_id' => '1',
            'comment' => 'foobar',
        ]);
    }

    /** @test */
    public function it_lets_you_set_pivot_data_while_creating_relation_with_custom_pivot_accessor_and_relation_name()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com",
                    name: "Jane Doe",
                    password: "secret",
                    customRoles: [
                        { name: "administrator", customPivot: { comment: "foobar" } }
                    ],
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('roles', ['id' => '1', 'name' => 'administrator']);
        $this->assertDatabaseHas('role_user', [
            'user_id' => '2',
            'role_id' => '1',
            'comment' => 'foobar',
        ]);
    }

    /** @test */
    public function it_lets_you_attach_a_morph_to_relation()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $article = factory(Models\Article::class)->create();

        $query = '
            mutation {
                createUpvote(input: {
                    upvoteableId: { article: "'.$article->id.'" }
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('upvotes', ['upvoteable_id' => $article->id]);
    }

    /** @test */
    public function it_lets_you_create_a_morph_to_relation()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $article = factory(Models\Article::class)->create();

        $query = '
            mutation {
                createUpvote(input: {
                    upvoteable: { comment: {
                        body: "Cool story bro"
                        userId: "'.$user->id.'"
                        articleId: "'.$article->id.'"
                    } }
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('comments', ['body' => 'Cool story bro']);
        $this->assertDatabaseHas('upvotes', ['upvoteable_id' => '1']);
    }

    /** @test */
    public function it_lets_you_attach_a_morph_many_relation()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $article = factory(Models\Article::class)->create();
        $upvote = factory(Models\Upvote::class)->create();

        $query = '
            mutation {
                createComment(input: {
                    body: "Cool story bro"
                    userId: "'.$user->id.'"
                    articleId: "'.$article->id.'"
                    upvoteIds: ["'.$upvote->id.'"]
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('comments', ['article_id' => $article->id]);
        $this->assertDatabaseHas('upvotes', ['upvoteable_id' => '1', 'upvoteable_type' => Comment::class]);
    }

    /** @test */
    public function it_lets_you_create_a_morph_many_relation()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $article = factory(Models\Article::class)->create();

        $query = '
            mutation {
                createComment(input: {
                    body: "Cool story bro"
                    userId: "'.$user->id.'"
                    articleId: "'.$article->id.'"
                    upvotes: [
                        {  }
                    ]
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('comments', ['article_id' => $article->id]);
        $this->assertDatabaseHas('upvotes', ['upvoteable_id' => '1', 'upvoteable_type' => Comment::class]);
    }

    /** @test */
    public function it_lets_you_attach_a_morphed_by_many_relation()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $article = factory(Article::class)->create();

        $query = '
            mutation {
                createTag(input: {
                    name: "News"
                    articleIds: ["'.$article->id.'"]
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('tags', ['name' => 'News']);
        $this->assertDatabaseHas('taggables', ['tag_id' => '1', 'taggable_type' => Article::class, 'taggable_id' => '1']);
    }

    /** @test */
    public function it_lets_you_create_a_morphed_by_many_relation()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createTag(input: {
                    name: "News"
                    articles: [{
                        title: "Hello world"
                        slug: "hello-world"
                        content: "Lorem ipsum"
                        userId: "'.$user->id.'"
                    }]
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('articles', ['title' => 'Hello world']);
        $this->assertDatabaseHas('tags', ['name' => 'News']);
        $this->assertDatabaseHas('taggables', ['tag_id' => '1', 'taggable_type' => Article::class, 'taggable_id' => '1']);
    }

    /** @test */
    public function it_throws_exception_when_supplying_multiple_keys_to_polymorphic_input_field()
    {
        $this->withExceptionHandling();

        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $article = factory(Models\Article::class)->create();

        $query = '
            mutation {
                createUpvote(input: {
                    upvoteable: { comment: {
                        body: "Cool story bro"
                        userId: "'.$user->id.'"
                        articleId: "'.$article->id.'"
                    }, article: {
                        title: "This is wrong"
                        slug: "this-is-wrong"
                        content: "Lorem ipsum"
                    }}
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['message' => 'There must be only one key with polymorphic input. 2 given for relation upvoteable.']);
        $this->assertDatabaseMissing('comments', ['body' => 'Cool story bro']);
        $this->assertDatabaseMissing('articles', ['title' => 'This is wrong']);
        $this->assertDatabaseMissing('upvotes', ['upvoteable_id' => '1']);
    }
}
