<?php

namespace Scrn\Bakery\Tests\Queries;

use Gate;
use Schema;
use Eloquent;
use Scrn\Bakery\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Scrn\Bakery\Tests\Stubs;
use Scrn\Bakery\Tests\WithDatabase;
use Scrn\Bakery\Mutations\CreateMutation;

class CreateMutationTest extends TestCase
{
    use WithDatabase;

    protected function getEnvironmentSetUp($app)
    {
        $this->setupDatabase($app);
    }

    public function setUp()
    {
        parent::setUp();

        Eloquent::reguard();
        $this->migrateDatabase();
    }

    /** @test */
    public function it_creates_an_entity()
    {
        $this->actingAs($this->createUser());
        Gate::policy(Stubs\Post::class, Stubs\Policies\PostPolicy::class);

        $query = new CreateMutation(Stubs\Post::class);
        $result = $query->resolve(null, ['input' => ['title' => 'Hello world!']]);

        $this->assertDatabaseHas('posts', ['title' => 'Hello world!']);
    }
}
