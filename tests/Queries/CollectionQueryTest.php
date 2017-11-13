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

        Eloquent::Unguard();

        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->string('slug')->nullable();
            $table->string('title')->nullable();
            $table->string('body')->nullable();
            $table->integer('comments')->nullable();
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
    public function it_filters_by_field_not_contain_matches()
    {
        Model::create(['title' => 'Hello world']);
        Model::create(['title' => 'Hello mars']); 
        Model::create(['title' => 'Goodbye world']); 

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_not_contains' => 'hello']]);

        $this->assertCount(1, $result->items());
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

    /** @test */
    public function it_filters_by_a_list_of_possible_values_for_a_field()
    {
        Model::create(['title' => 'foo']);
        Model::create(['title' => 'bar']); 
        Model::create(['title' => 'baz']); 

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_in' => ['foo', 'bar']]]);

        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_filters_by_a_list_of_excluded_values_for_a_field()
    {
        Model::create(['title' => 'foo']);
        Model::create(['title' => 'bar']); 
        Model::create(['title' => 'baz']); 

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_not_in' => ['foo', 'bar']]]);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_less_then_a_certain_value()
    {
        Model::create(['comments' => 5]);
        Model::create(['comments' => 10]); 

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['comments_lt' => 6]]);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_less_then_or_equal_a_certain_value()
    {
        Model::create(['comments' => 5]);
        Model::create(['comments' => 10]); 

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['comments_lte' => 5]]);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_greater_then_a_certain_value()
    {
        Model::create(['comments' => 5]);
        Model::create(['comments' => 10]); 

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['comments_gt' => 5]]);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_greater_then_or_equal_a_certain_value()
    {
        Model::create(['comments' => 5]);
        Model::create(['comments' => 10]); 

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['comments_gte' => 5]]);

        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_filters_by_starts_with_a_certain_value()
    {
        Model::create(['title' => 'Hello world']);
        Model::create(['title' => 'Hello mars']); 
        Model::create(['title' => 'Goodbye world']); 

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_starts_with' => 'hel']]);

        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_filters_by_starts_not_with_a_certain_value()
    {
        Model::create(['title' => 'Hello world']);
        Model::create(['title' => 'Hello mars']); 
        Model::create(['title' => 'Goodbye world']); 

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_not_starts_with' => 'hel']]);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_ends_with_a_certain_value()
    {
        Model::create(['title' => 'Hello world']);
        Model::create(['title' => 'Hello mars']); 
        Model::create(['title' => 'Goodbye world']); 

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_ends_with' => 'world']]);

        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_filters_by_ends_not_with_a_certain_value()
    {
        Model::create(['title' => 'Hello world']);
        Model::create(['title' => 'Hello mars']); 
        Model::create(['title' => 'Goodbye world']); 

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_not_ends_with' => 'world']]);

        $this->assertCount(1, $result->items());
    }
}
