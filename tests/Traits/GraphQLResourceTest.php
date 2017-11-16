<?php

namespace Scrn\Bakery\Tests\Traits;

use Scrn\Bakery\Tests\Stubs;
use Scrn\Bakery\Tests\TestCase;
use Scrn\Bakery\Tests\WithDatabase;

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
                    ]
                ],
                [
                    'body' => 'Great post!',
                    'user' => [
                        'name' => 'John Stone',
                        'email' => 'j.stone@example.com',
                        'password' => 'secret',
                    ]
                ],
            ],
        ];

        app(Stubs\Post::class)->createWithGraphQLInput($input);
        $this->assertDatabaseHas('posts', ['title' => 'Hello world!', 'user_id' => '1']);
        $this->assertDatabaseHas('comments', ['body' => 'First!', 'user_id' => '2', 'post_id' => '1']);
        $this->assertDatabaseHas('comments', ['body' => 'Great post!', 'user_id' => '3', 'post_id' => '1']);
    }

    /** @test */
    public function it_can_save_nested_mutation_input_v2()
    {
        $input = [
            'number' => '+31612345678',
            'user' => [
                'name' => 'John Doe',
                'email' => 'j.doe@example.com',
                'password' => 'secret',
                'posts' => [
                    ['title' => 'Post one'],
                    ['title' => 'Post two'],
                ]
            ],
        ];

        app(Stubs\Phone::class)->createWithGraphQLInput($input);
        $this->assertDatabaseHas('phones', ['number' => '+31612345678', 'user_id' => '1']);
        $this->assertDatabaseHas('users', ['name' => 'John Doe']);
        $this->assertDatabaseHas('posts', ['title' => 'Post one', 'user_id' => '1']);
        $this->assertDatabaseHas('posts', ['title' => 'Post two', 'user_id' => '1']);
    }

    /** @test */
    public function it_can_save_has_one_nested_relations()
    {
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
        $this->assertDatabaseHas('phones', ['number' => '+31612345678', 'user_id' => '1']);
        $this->assertDatabaseHas('role_user', ['user_id' => '1', 'role_id' => '1']);
        $this->assertDatabaseHas('role_user', ['user_id' => '1', 'role_id' => '2']);
    }
}
