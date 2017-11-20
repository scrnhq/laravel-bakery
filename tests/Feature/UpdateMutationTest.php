<?php

namespace Bakery\Tests\Feature;

use Gate;
use Eloquent;
use Bakery\Tests\Stubs;
use Bakery\Tests\TestCase;
use Bakery\Tests\WithDatabase;
use Bakery\Http\Controller\BakeryController;
use Illuminate\Auth\Access\AuthorizationException;
use Bakery\Exceptions\TooManyResultsException;

class UpdateMutationTest extends TestCase
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
    public function it_does_not_allow_updating_entity_as_guest()
    {
        $this->expectException(AuthorizationException::class);

        $user = $this->createUser();
        $post = new Stubs\Post(['title' => 'Hello world!']);
        $post->user()->associate($user);
        $post->save();

        $query = '
            mutation {
                updatePost(
                    id: ' . $post->id . '
                    input: { title: "Hello world! (updated)" }
               ) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
    }

    /** @test */
    public function it_does_not_allow_updating_entity_as_user_when_there_is_no_policy()
    {
        $this->expectException(AuthorizationException::class);

        $user = $this->createUser();
        $post = new Stubs\Post(['title' => 'Hello world!']);
        $post->user()->associate($user);
        $post->save();

        $this->actingAs($user);

        $query = '
            mutation {
                updatePost(
                    id: 1,
                    input: { title: "Hello world! (updated)" }
                ) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
    }

    /** @test */
    public function it_does_allow_updating_entity_as_user_when_it_is_allowed_by_policy()
    {
        Gate::policy(Stubs\Post::class, Stubs\Policies\PostPolicy::class);

        $user = $this->createUser();
        $post = new Stubs\Post(['title' => 'Hello world!']);
        $post->user()->associate($user);
        $post->save();

        $this->actingAs($user);

        $query = '
            mutation {
                updatePost(
                    id: 1,
                    input: { title: "Hello world! (updated)" }
                ) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $this->assertDatabaseHas('posts', ['title' => 'Hello world! (updated)']);
    }

    /** @test */
    public function it_throws_too_many_results_exception_when_lookup_is_not_specific_enough()
    {
        $this->expectException(TooManyResultsException::class);
        Gate::policy(Stubs\Post::class, Stubs\Policies\PostPolicy::class);

        $user = $this->createUser();
        $post = new Stubs\Post(['title' => 'Hello world!', 'slug' => 'hello-world']);
        $post->user()->associate($user);
        $post->save();

        $post2 = new Stubs\Post(['title' => 'Hello world! (part two)', 'slug' => 'hello-world']);
        $post2->user()->associate($user);
        $post2->save();

        $this->actingAs($user);

        $query = '
            mutation {
                updatePost(
                    slug: "hello-world",
                    input: { title: "Hello world! (updated)" }
                ) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
    }

    /** @test */
    public function it_lets_you_do_deep_nested_update_mutations()
    {
        Gate::policy(Stubs\User::class, Stubs\Policies\UserPolicy::class);
        $user = $this->createUser();
        $this->actingAs($user);

        $post = new Stubs\Post(['title' => 'Hello World!', 'slug' => 'hello-world']);
        $post->user()->associate($user);
        $post->save();

        $query = '
            mutation {
                updateUser(
                    id: ' . $user->id . '
                    input: {
                        name: "Jona Doe",
                        posts: [{
                            title: "This is my second post!",
                            comments: [
                                { body: "First!", userId: ' . $user->id . ' },
                                { body: "Great post!", userId: ' . $user->id  . '},
                            ]
                        }]
                    }
                ) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $this->assertDatabaseHas('users', ['name' => 'Jona Doe']);
        $this->assertDatabaseHas('posts', ['title' => 'Hello World!', 'user_id' => $user->id]);
        $this->assertDatabaseHas('posts', ['title' => 'This is my second post!', 'user_id' => $user->id]);
        $this->assertDatabaseHas('comments', ['body' => 'First!', 'post_id' => '2']);
        $this->assertDatabaseHas('comments', ['body' => 'Great post!', 'post_id' => '2']);
    }
}
