<?php

namespace Scrn\Bakery\Tests;

use GraphQL\Type\Definition\ObjectType;
use Scrn\Bakery\Exceptions\TypeNotFound;
use Scrn\Bakery\Support\Facades\Bakery;
use Scrn\Bakery\Tests\Stubs;

class BakeryTest extends TestCase
{
    /** @test */
    public function it_can_register_a_model()
    {
        Bakery::addModel(Stubs\EmptyModel::class);

        $models = Bakery::getModels();
        $this->assertContains(Stubs\EmptyModel::class, $models);
    }

    /** @test */
    public function it_returns_the_object_type_for_a_model()
    {
        $type = Bakery::type('Model');

        $this->assertInstanceOf(ObjectType::class, $type);

        $typeOther = Bakery::type('Model');

        $this->assertSame($type, $typeOther);
    }

    /** @test */
    public function it_throws_exception_for_unregistered_type()
    {
        $this->expectException(TypeNotFound::class);

        Bakery::type('WrongType');
    }

    /** @test */
    public function it_returns_the_schema()
    {
        $schema = Bakery::schema();

        $this->assertArrayHasKey('Model', $schema->getTypeMap());
    }

    /** @test */
    public function it_registers_an_entity_query_field_for_a_model()
    {
        $queries = app('bakery')->getQueries();

        $results = array_values(array_filter($queries, function($query) {
            return $query['name'] === 'model'; 
        })); 

        $this->assertCount(1, $results);
    }

    /** @test */
    public function it_registers_a_collection_query_for_a_model()
    {
        $queries = app('bakery')->getQueries();

        $results = array_values(array_filter($queries, function ($query) {
            return $query['name'] === 'models'; 
        }));

        $this->assertCount(1, $results);
    }
}
