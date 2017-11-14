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
        ]);
    }

    protected function setUp()
    {
        parent::setUp();
        $this->migrateDatabase();
    }

    /** @test */
    public function it_does_not_allow_creating_entity_as_guest() 
    {
        $this->expectException(AuthorizationException::class);

        $query = '
            mutation {
                createModel(input: {
                    title: "foo"
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
                createModel(input: {
                    title: "foo"
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

        Gate::policy(Stubs\Model::class, Stubs\Policies\ModelPolicy::class);

        $query = '
            mutation {
                createModel(input: {
                    title: "foo"
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $this->assertDatabaseHas('models', ['title' => 'foo']);
    }
}
