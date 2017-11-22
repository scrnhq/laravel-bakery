<?php

namespace Bakery\Tests\Traits;

use Bakery\Tests\Stubs;
use Bakery\Tests\TestCase;
use Bakery\Tests\WithDatabase;
use Illuminate\Contracts\Auth\Access\Gate;

class GraphQLResourceTest extends TestCase
{
    use WithDatabase;

    protected function getEnvironmentSetUp($app)
    {
        $this->setupDatabase($app);

        $app['config']->set('bakery.models', [
            Stubs\Post::class,
            Stubs\Comment::class,
            Stubs\User::class,
            Stubs\Phone::class,
            Stubs\Role::class,
        ]);
    }

    protected function setUp()
    {
        parent::setUp();
        app(Gate::class)->policy(Stubs\User::class, Stubs\Policies\UserPolicy::class);
        app(Gate::class)->policy(Stubs\Post::class, Stubs\Policies\PostPolicy::class);
        app(Gate::class)->policy(Stubs\Comment::class, Stubs\Policies\CommentPolicy::class);
        app(Gate::class)->policy(Stubs\Phone::class, Stubs\Policies\PhonePolicy::class);
        $this->migrateDatabase();
    }

    /** @test */
    public function it_has_fields()
    {
        $fields = app(Stubs\Model::class)->fields();

        $this->assertInternalType('array', $fields);
    }

    /** @test */
    public function it_can_save_nested_mutation_input()
    {
        $this->actingAs($this->createUser());
        $input = [
            'title' => 'Hello world!',
            'user' => [
                'name' => 'John Doe',
                'email' => 'j.doe@example.com',
                'password' => 'secret',
            ],
            'comments' => [
                [
                    'body' => 'First!',
                    'user' => [
                        'name' => 'Henk Green',
                        'email' => 'h.green@example.com',
                        'password' => 'secret',
                    ],
                ],
                [
                    'body' => 'Great post!',
                    'user' => [
                        'name' => 'John Stone',
                        'email' => 'j.stone@example.com',
                        'password' => 'secret',
                    ],
                ],
            ],
        ];

        app(Stubs\Post::class)->createWithGraphQLInput($input);
        $this->assertDatabaseHas('posts', ['title' => 'Hello world!', 'user_id' => '2']);
        $this->assertDatabaseHas('comments', ['body' => 'First!', 'user_id' => '3', 'post_id' => '1']);
        $this->assertDatabaseHas('comments', ['body' => 'Great post!', 'user_id' => '4', 'post_id' => '1']);
    }

    /** @test */
    public function it_can_save_nested_mutation_input_v2()
    {
        $this->actingAs($this->createUser());
        $input = [
            'number' => '+31612345678',
            'user' => [
                'name' => 'John Doe',
                'email' => 'j.doe@example.com',
                'password' => 'secret',
                'posts' => [
                    ['title' => 'Post one'],
                    ['title' => 'Post two'],
                ],
            ],
        ];

        app(Stubs\Phone::class)->createWithGraphQLInput($input);
        $this->assertDatabaseHas('phones', ['number' => '+31612345678', 'user_id' => '2']);
        $this->assertDatabaseHas('users', ['name' => 'John Doe']);
        $this->assertDatabaseHas('posts', ['title' => 'Post one', 'user_id' => '2']);
        $this->assertDatabaseHas('posts', ['title' => 'Post two', 'user_id' => '2']);
    }

    /** @test */
    public function it_can_save_has_one_nested_relations()
    {
        $this->actingAs($this->createUser());
        $input = [
            'name' => 'John Doe',
            'email' => 'j.doe@example.com',
            'password' => 'secret',
            'phone' => [
                'number' => '+31612345678',
            ],
            'roles' => [
                ['name' => 'admin'],
                ['name' => 'moderator'],
            ],
        ];

        app(Stubs\User::class)->createWithGraphQLInput($input);
        $this->assertDatabaseHas('users', ['name' => 'John Doe']);
        $this->assertDatabaseHas('phones', ['number' => '+31612345678', 'user_id' => '2']);
        $this->assertDatabaseHas('role_user', ['user_id' => '2', 'role_id' => '1']);
        $this->assertDatabaseHas('role_user', ['user_id' => '2', 'role_id' => '2']);
    }

    /** @test */
    public function it_aborts_the_transaction_when_nested_relations_not_allowed_through_gate()
    {
        $this->actingAs($this->createUser());
        $input = [
            'name' => 'admin',
            'users' => [
                ['name' => 'Jane Doe', 'email' => 'j.doe@example.com']
            ]
        ];

        app(Stubs\Role::class)->createWithGraphQLInput($input);
        $this->assertDatabaseMissing('roles', ['name' => 'admin']);
        $this->assertDatabaseMissing('users', ['name' => 'Jane Doe']);
        $this->assertDatabaseMissing('role_user', ['user_id' => '2', 'role_id' => 1]);
    }
}
