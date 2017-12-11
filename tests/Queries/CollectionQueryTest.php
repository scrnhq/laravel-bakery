<?php

namespace Bakery\Tests\Queries;

use Schema;
use Eloquent;
use Bakery\Tests\TestCase;
use Bakery\Exceptions\PaginationMaxCountExceededException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Bakery\Tests\Stubs\User;
use Bakery\Tests\Stubs\Post;
use Bakery\Tests\Stubs\Model;
use Bakery\Tests\WithDatabase;
use Bakery\Queries\CollectionQuery;

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
        $this->migrateDatabase();
        Eloquent::Unguard();
    }

    /** @test */
    public function it_resolves_a_collection_without_arguments()
    {
        Model::create();
        Model::create();

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, [], null);

        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_filters_by_exact_field_match()
    {
        Model::create(['title' => 'foo']);
        Model::create(['title' => 'bar']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title' => 'foo']], null);

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
        ]], null);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_field_contain_matches()
    {
        Model::create(['title' => 'Hello world']);
        Model::create(['title' => 'Hello mars']);
        Model::create(['title' => 'Goodbye world']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_contains' => 'hello']], null);

        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_filters_by_field_not_contain_matches()
    {
        Model::create(['title' => 'Hello world']);
        Model::create(['title' => 'Hello mars']);
        Model::create(['title' => 'Goodbye world']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_not_contains' => 'hello']], null);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_not_matching_field()
    {
        Model::create(['title' => 'foo']);
        Model::create(['title' => 'foo']);
        Model::create(['title' => 'bar']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_not' => 'foo']], null);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_a_list_of_possible_values_for_a_field()
    {
        Model::create(['title' => 'foo']);
        Model::create(['title' => 'bar']);
        Model::create(['title' => 'baz']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_in' => ['foo', 'bar']]], null);

        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_filters_by_a_list_of_excluded_values_for_a_field()
    {
        Model::create(['title' => 'foo']);
        Model::create(['title' => 'bar']);
        Model::create(['title' => 'baz']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_not_in' => ['foo', 'bar']]], null);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_less_then_a_certain_value()
    {
        Model::create(['comments' => 5]);
        Model::create(['comments' => 10]);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['comments_lt' => 6]], null);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_less_then_or_equal_a_certain_value()
    {
        Model::create(['comments' => 5]);
        Model::create(['comments' => 10]);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['comments_lte' => 5]], null);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_greater_then_a_certain_value()
    {
        Model::create(['comments' => 5]);
        Model::create(['comments' => 10]);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['comments_gt' => 5]], null);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_greater_then_or_equal_a_certain_value()
    {
        Model::create(['comments' => 5]);
        Model::create(['comments' => 10]);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['comments_gte' => 5]], null);

        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_filters_by_starts_with_a_certain_value()
    {
        Model::create(['title' => 'Hello world']);
        Model::create(['title' => 'Hello mars']);
        Model::create(['title' => 'Goodbye world']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_starts_with' => 'hel']], null);

        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_filters_by_starts_not_with_a_certain_value()
    {
        Model::create(['title' => 'Hello world']);
        Model::create(['title' => 'Hello mars']);
        Model::create(['title' => 'Goodbye world']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_not_starts_with' => 'hel']], null);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_filters_by_ends_with_a_certain_value()
    {
        Model::create(['title' => 'Hello world']);
        Model::create(['title' => 'Hello mars']);
        Model::create(['title' => 'Goodbye world']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_ends_with' => 'world']], null);

        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_filters_by_ends_not_with_a_certain_value()
    {
        Model::create(['title' => 'Hello world']);
        Model::create(['title' => 'Hello mars']);
        Model::create(['title' => 'Goodbye world']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['filter' => ['title_not_ends_with' => 'world']], null);

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_orders_by_a_field_in_ascending_order()
    {
        $first = Model::create(['title' => 'Hello mars']);
        $second = Model::create(['title' => 'Hello world']);
        $third = Model::create(['title' => 'Goodbye world']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['orderBy' => 'title_ASC'], null);

        $this->assertTrue($result->items()[0]->is($third));
        $this->assertTrue($result->items()[1]->is($first));
        $this->assertTrue($result->items()[2]->is($second));
    }

    /** @test */
    public function it_orders_by_a_field_in_descending_order()
    {
        $first = Model::create(['title' => 'Hello mars']);
        $second = Model::create(['title' => 'Hello world']);
        $third = Model::create(['title' => 'Goodbye world']);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['orderBy' => 'title_DESC'], null);

        $this->assertTrue($result->items()[0]->is($second));
        $this->assertTrue($result->items()[1]->is($first));
        $this->assertTrue($result->items()[2]->is($third));
    }

    /** @test */
    public function it_throws_an_exception_if_pagination_max_count_is_exceeded()
    {
        $this->expectException(PaginationMaxCountExceededException::class);

        $query = new CollectionQuery(Model::class);
        $result = $query->resolve(null, ['count' => 1001], null);
    }
}
