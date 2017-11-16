<?php

namespace Scrn\Bakery\Tests\Feature;

use Gate;
use Eloquent;
use Scrn\Bakery\Tests\Stubs;
use Scrn\Bakery\Tests\TestCase;
use Scrn\Bakery\Tests\WithDatabase;
use Scrn\Bakery\Http\Controller\BakeryController;
use Illuminate\Auth\Access\AuthorizationException;
use Scrn\Bakery\Exceptions\TooManyResultsException;

class DeleteMutationTest extends TestCase
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
    public function it_does_not_allow_deleting_entity_as_guest() 
    {
        $this->expectException(AuthorizationException::class);

        $user = $this->createUser();
        $post = new Stubs\Post(['title' => 'Hello world!']); 
        $post->user()->associate($user);
        $post->save();

        $query = '
            mutation {
                deletePost(id: ' . $post->id . ')
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
    }

    /** @test */
    public function it_does_not_allow_deleting_entity_as_user_when_there_is_no_policy() 
    {
        $this->expectException(AuthorizationException::class);

        $user = $this->createUser();
        $post = new Stubs\Post(['title' => 'Hello world!']); 
        $post->user()->associate($user);
        $post->save();

        $this->actingAs($user);

        $query = '
            mutation {
                deletePost(id: ' . $post->id . ')
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
    }

    /** @test */
    public function it_does_allow_deleting_entity_as_user_when_it_is_allowed_by_policy() 
    {
        Gate::policy(Stubs\Post::class, Stubs\Policies\PostPolicy::class);

        $user = $this->createUser();
        $post = new Stubs\Post(['title' => 'Hello world!']); 
        $post->user()->associate($user);
        $post->save();

        $this->actingAs($user);

        $query = '
            mutation {
                deletePost(id: ' . $post->id . ')
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $this->assertDatabaseMissing('posts', ['title' => 'Hello world! (updated)']);
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
                deletePost(slug: "hello-world")
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
    }
}
