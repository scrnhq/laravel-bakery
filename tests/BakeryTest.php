<?php

namespace Scrn\Bakery\Tests;

use Scrn\Bakery\Tests\Stubs;
use Scrn\Bakery\Queries\EntityQuery;
use Scrn\Bakery\Queries\CollectionQuery;
use Scrn\Bakery\Exceptions\ModelNotRegistered;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

class BakeryTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('bakery.models', [
            Stubs\Model::class,
        ]);
    }

    /** @test */
    public function it_returns_an_entity_type_for_a_model()
    {
        $expected = new ObjectType([
            'name' => 'Model',
            'fields' => [
                'id' => Type::ID(),
            ],
        ]);

        $this->assertEquals($expected, app('bakery')->entityType(Stubs\Model::class));
    }

    /** @test */
    public function it_throws_exception_for_unregistered_model()
    {
        $this->expectException(ModelNotRegistered::class);

        app('bakery')->entityType(Stubs\EmptyModel::class);
    }

    /** @test */
    public function it_registers_an_entity_query_for_a_model() 
    {
        $queries = app('bakery')->getQueries();

        $results = array_values(array_filter($queries, function($query) {
            return get_class($query) === EntityQuery::class;
        })); 

        $this->assertCount(1, $results);
        $this->assertEquals($results[0]->name, 'model');
    }

    /** @test */
    public function it_registers_a_collection_query_for_a_model()
    {
        $queries = app('bakery')->getQueries();
        
        $results = array_values(array_filter($queries, function($query) {
            return get_class($query) === CollectionQuery::class;
        })); 


        $this->assertCount(1, $results); 
        $this->assertEquals($results[0]->name, 'models');
    }
}