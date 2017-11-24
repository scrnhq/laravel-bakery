<?php

namespace Bakery\Tests;

use Bakery\Exceptions\TypeNotFound;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Type;
use GraphQL\Type\Definition\ObjectType;

class BakeryTest extends TestCase
{
    /** @test */
    public function it_returns_the_type()
    {
        Bakery::addType(new Type(), 'Type');

        $type = Bakery::type('Type');

        $this->assertInstanceOf(ObjectType::class, $type);
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
}
