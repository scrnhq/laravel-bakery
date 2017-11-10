<?php

namespace Scrn\Bakery\Tests\Queries;

use Schema;
use Eloquent;
use Scrn\Bakery\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Scrn\Bakery\Tests\Stubs\Model;
use Scrn\Bakery\Tests\WithDatabase;
use Scrn\Bakery\Queries\CollectionQuery;

class CollectionQueryTest extends TestCase
{
    use WithDatabase;

    protected function getEnvironmentSetUp($app)
    {
        $this->setupDatabase($app);
    }

    /** @test */
    public function it_resolves_a_collection_without_arguments()
    {
        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

        Model::create();
        Model::create();

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve();

        // $this->assertCount(2, $result['items']);
    }
}
