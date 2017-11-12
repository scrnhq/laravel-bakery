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

    public function setUp()
    {
        parent::setUp();

        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->string('slug')->nullable();
            $table->string('title')->nullable();
            $table->string('body')->nullable();
            $table->timestamps();
        });
    }

    /** @test */
    public function it_resolves_a_collection_without_arguments()
    {
        Model::create();
        Model::create();

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, []);

        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_filters_by_exact_field_match()
    {
        Model::create(['title' => 'foo']);
        Model::create(['title' => 'bar']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title' => 'foo']]);

        $this->assertCount(1, $result->items());
    }
    
    /** @test */
    public function it_filters_by_combination_of_exact_field_matches()
    {
        Model::create(['title' => 'foo', 'body' => 'bar']);
        Model::create(['title' => 'foo', 'body' => 'baz']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => [
            'title' => 'foo',
            'body'  => 'bar'
        ]]);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_field_contain_matches()
    {
        Model::create(['title' => 'Hello world']);
        Model::create(['title' => 'Hello mars']); 
        Model::create(['title' => 'Goodbye world']); 

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_contains' => 'hello']]);

        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_filters_by_not_matching_field()
    {
        Model::create(['title' => 'foo']);
        Model::create(['title' => 'foo']); 
        Model::create(['title' => 'bar']); 

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_not' => 'foo']]);

        $this->assertCount(1, $result->items());
    }
}
