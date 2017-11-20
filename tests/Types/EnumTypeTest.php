<?php

namespace Bakery\Tests\Types;

use GraphQL\Type\Definition\EnumType;
use Bakery\Tests\Stubs\EnumTypeStub;
use Bakery\Tests\TestCase;

class EnumTypeTest extends TestCase
{
    /** @test */
    public function it_returns_the_enum_object_type()
    {
        $type = new EnumTypeStub();
        $objectType = $type->toGraphQLType();

        $this->assertInstanceOf(EnumType::class, $objectType);
        $this->assertEquals($type->name, $objectType->name);
    }
}
