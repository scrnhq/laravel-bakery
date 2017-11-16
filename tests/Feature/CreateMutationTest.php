<?php

namespace Scrn\Bakery\Tests\Feature;

use Gate;
use Eloquent;
use Scrn\Bakery\Tests\Stubs;
use Scrn\Bakery\Tests\TestCase;
use Scrn\Bakery\Tests\WithDatabase;
use Scrn\Bakery\Http\Controller\BakeryController;
use Illuminate\Auth\Access\AuthorizationException;

class CreateMutationTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $this->setupDatabase($app);

        $app['config']->set('bakery.models', [
            Stubs\Model::class,
            Stubs\Post::class,
            Stubs\Comment::class,
            Stubs\Phone::class,
            Stubs\User::class,
            Stubs\Role::class,
        ]);
    }

    protected function setUp()
    {
        parent::setUp();
        Eloquent::reguard();
        $this->migrateDatabase();
    }

    /** @test */
    public function it_does_not_allow_creating_entity_as_guest() 
    {
        $this->expectException(AuthorizationException::class);

        $query = '
            mutation {
                createPost(input: {
                    title: "Hello world!"
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
    }

    /** @test */
    public function it_does_not_allow_creating_entity_as_user_when_there_is_no_policy() 
    {
        $this->expectException(AuthorizationException::class);
        $this->actingAs($this->createUser());

        $query = '
            mutation {
                createPost(input: {
                    title: "Hello world!"
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
    }

    /** @test */
    public function it_does_allow_creating_entity_as_user_when_it_is_allowed_by_policy() 
    {
        $this->actingAs($this->createUser());

        Gate::policy(Stubs\Post::class, Stubs\Policies\PostPolicy::class);

        $query = '
            mutation {
                createPost(input: {
                    title: "Hello world!"
                    userId: 1,
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $this->assertDatabaseHas('posts', ['title' => 'Hello world!']);
    }

    /** @test */
    public function it_lets_you_create_a_has_one_relationship()
    {
        $this->actingAs($this->createUser());
        Gate::policy(Stubs\User::class, Stubs\Policies\UserPolicy::class);


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
        $this->assertDatabaseHas('phones', ['number' => '+31612345678', 'user_id' => '1']);
    }

    /** @test */
    public function it_lets_you_save_a_has_one_relationship() 
    {
        $this->actingAs($this->createUser());
        Gate::policy(Stubs\User::class, Stubs\Policies\UserPolicy::class);

        $phone = Stubs\Phone::create(['number' => '+31612345678']);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com",
                    name: "Jane Doe",
                    password: "secret",
                    phoneId: "1",
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $this->assertDatabaseHas('phones', ['user_id' => '1']);
    }

    /** @test */
    public function it_lets_you_create_a_belongs_to_relationship() 
    {
        $this->actingAs($this->createUser());
        Gate::policy(Stubs\Phone::class, Stubs\Policies\PhonePolicy::class);

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
        $this->assertDatabaseHas('phones', ['number' => '+31612345678', 'user_id' => '1']);
        $this->assertDatabaseHas('users', ['name' => 'Jane Doe']);
    }

    /** @test */
    public function it_lets_you_to_assign_a_belongs_to_relationship()
    {
        $this->actingAs($this->createUser());

        $post = new Stubs\Post(['title' => 'Hello world!']);
        $post->user_id = 1;
        $post->save();

        $commenter = Stubs\User::create([
            'name' => 'Jane Doe',
            'password' => 'secret',
            'email' => 'jane.doe@example.com',
        ]);

        Gate::policy(Stubs\Comment::class, Stubs\Policies\CommentPolicy::class);

        $query = '
            mutation {
                createComment(input: {
                    body: "Cool story bro",
                    postId: ' . $post->id . '
                    userId: ' . $commenter->id . '
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $this->assertDatabaseHas('comments', ['id' => '1', 'post_id' => $post->id, 'user_id' => $commenter->id]);
    }

    /** @test */
    public function it_lets_you_assign_a_many_to_many_relationship()
    {
        $this->actingAs($this->createUser());
        Gate::policy(Stubs\User::class, Stubs\Policies\UserPolicy::class);

        $roles = [
            Stubs\Role::create(['name' => 'moderator']),
            Stubs\Role::create(['name' => 'writer']),
        ];

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com",
                    name: "Jane Doe",
                    password: "secret", 
                    roleIds: [1, 2] 
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $this->assertDatabaseHas('role_user', ['role_id' => '1', 'user_id' => '1']);
        $this->assertDatabaseHas('role_user', ['role_id' => '2', 'user_id' => '1']);
    }

    /** @test */
    public function it_lets_you_insert_a_has_many_relationship()
    {
        $this->actingAs($this->createUser());
        Gate::policy(Stubs\Post::class, Stubs\Policies\PostPolicy::class);

        $query = '
            mutation {
                createPost(input: {
                    title: "Hello World",
                    userId: 1,
                    comments: [
                        { body: "First!", userId: 1 },
                        { body: "Great post!", userId: 1 },
                    ]
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $this->assertDatabaseHas('comments', ['body' => 'First!', 'post_id' => '1']);
        $this->assertDatabaseHas('comments', ['body' => 'Great post!', 'post_id' => '1']);
    }

    /** @test */
    public function it_lets_you_do_deep_nested_create_mutations()
    {
        $this->actingAs($this->createUser());
        Gate::policy(Stubs\User::class, Stubs\Policies\UserPolicy::class);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com",
                    name: "Jane Doe",
                    password: "secret", 
                    posts: [{
                        title: "Hello World!",
                        comments: [
                            { body: "First!", userId: 1 },
                            { body: "Great post!", userId: 1 },
                        ]
                    }]
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $this->assertDatabaseHas('users', ['email' => 'jane.doe@example.com', 'name' => 'Jane Doe']);
        $this->assertDatabaseHas('posts', ['title' => 'Hello World!', 'user_id' => '1']);
        $this->assertDatabaseHas('comments', ['body' => 'First!', 'post_id' => '1']);
        $this->assertDatabaseHas('comments', ['body' => 'Great post!', 'post_id' => '1']);
    }
}
