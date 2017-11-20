<?php

namespace Bakery\Tests;

use GraphQL\Type\Definition\ObjectType;
use Bakery\Exceptions\TypeNotFound;
use Bakery\Support\Facades\Bakery;
use Bakery\Tests\Stubs;

class BakeryTest extends TestCase
{
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
}
