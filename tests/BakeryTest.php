<?php

namespace Bakery\Tests;

use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\Type;
use Bakery\Exceptions\TypeNotFound;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;

class DummyType extends Type
{
    //
}

class BakeryTest extends FeatureTestCase
{
    /** @test */
    // public function it_returns_the_type()
    // {
    //     Bakery::addType(new DummyType(), 'Type');

    //     $type = Bakery::type('Type');

    //     $this->assertInstanceOf(ObjectType::class, $type);
    // }

    /** @test */
    public function it_throws_exception_for_unregistered_type()
    {
        $this->expectException(TypeNotFound::class);

        Bakery::type('WrongType');
    }
}
