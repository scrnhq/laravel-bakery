<?php

namespace Scrn\Bakery\Tests\Traits;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Scrn\Bakery\Tests\Stubs\EmptyModel;
use Scrn\Bakery\Tests\Stubs\Model;
use Scrn\Bakery\Tests\TestCase;

class GraphQLResourceTest extends TestCase
{
    /** @test */
    public function it_builds_an_object_type_from_an_empty_model()
    {
        $expected = new ObjectType([
            'name' => 'EmptyModel',
            'fields' => [],
        ]);

        $this->assertEquals($expected, app(EmptyModel::class)->toObjectType());
    }

    /** @test */
    public function it_builds_an_object_type_from_a_model_with_fields()
    {
        $expected = new ObjectType([
            'name' => 'Model',
            'fields' => [
                'id' => Type::ID(),
            ],
        ]);

        $this->assertEquals($expected, app(Model::class)->toObjectType());
    }
}