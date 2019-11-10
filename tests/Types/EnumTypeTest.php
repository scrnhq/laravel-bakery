<?php

namespace Bakery\Tests\Types;

use Bakery\Support\Schema;
use Bakery\Tests\Stubs\EnumTypeStub;
use Bakery\Tests\TestCase;
use GraphQL\Type\Definition\EnumType as GraphQLEnumType;

class EnumTypeTest extends TestCase
{
    /** @test */
    public function it_returns_the_enum_object_type()
    {
        $schema = new Schema();
        $type = new EnumTypeStub($schema->getRegistry());
        $objectType = $type->toType();

        $this->assertInstanceOf(GraphQLEnumType::class, $objectType);
        $this->assertEquals($type->name, $objectType->name);
    }
}
