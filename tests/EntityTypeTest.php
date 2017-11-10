<?php

namespace Scrn\Bakery\Tests;

use Closure;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Scrn\Bakery\Types\EntityType;
use Scrn\Bakery\Tests\Stubs;

class EntityTypeTest extends TestCase
{
    /** @test */
    public function it_returns_the_fields()
    {
        $type = new EntityType(Stubs\Model::class);
        $fields = $type->getFields();

        $this->assertArrayHasKey('id', $fields);
        $this->assertEquals($fields['id'], Type::ID());
        $this->assertEquals($fields['field'], Type::string());
    }

    /** @test */
    public function it_returns_the_attributes()
    {
        $type = new EntityType(Stubs\Model::class);
        $attributes = $type->getAttributes();

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('fields', $attributes);
        $this->assertInstanceOf(Closure::class, $attributes['fields']);
        $this->assertInternalType('array', $attributes['fields']());
    }

    /** @test */
    public function it_returns_the_object_type()
    {
        $type = new EntityType(Stubs\Model::class);
        $objectType = $type->toGraphQLType();

        $this->assertInstanceOf(ObjectType::class, $objectType);
        $this->assertEquals($objectType->name, $type->name);
    }

    /** @test */
    public function it_returns_the_name()
    {
        $type = new EntityType(Stubs\Model::class);
        $name = $type->name;

        $this->assertEquals('Model', $name);
    }
}
