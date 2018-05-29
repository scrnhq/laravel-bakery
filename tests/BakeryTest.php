<?php

namespace Bakery\Tests;

use Bakery\Types\Type;
use Bakery\Support\Facades\Bakery;
use Bakery\Exceptions\TypeNotFound;
use GraphQL\Type\Definition\ObjectType;

class BakeryTest extends FeatureTestCase
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
}
