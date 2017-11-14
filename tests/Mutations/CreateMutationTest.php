<?php

namespace Scrn\Bakery\Tests\Queries;

use Schema;
use Eloquent;
use Scrn\Bakery\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Gate;
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
        $this->migrateDatabase();
    }

    /** @test */
    public function it_creates_an_entity()
    {
        $this->actingAs($this->createUser());
        Gate::policy(Stubs\Model::class, Stubs\Policies\ModelPolicy::class);

        $query = new CreateMutation(Stubs\Model::class);
        $result = $query->resolve(null, ['input' => ['title' => 'foo']]);

        $this->assertDatabaseHas('models', ['title' => 'foo']);
    }

}
