<?php

namespace Bakery\Tests;

use Bakery\Types\EntityCollectionType;
use Bakery\Tests\Stubs;

class EntityCollectionTypeTest extends TestCase
{
    /** @test */
    public function it_returns_the_fields()
    {
        $type = new EntityCollectionType(Stubs\Model::class);
        $fields = $type->getFields();

        $this->assertArrayHasKey('pagination', $fields);
        $this->assertArrayHasKey('items', $fields);
    }

    /** @test */
    public function it_returns_the_name()
    {
        $type = new EntityCollectionType(Stubs\Model::class);
        $name = $type->name;

        $this->assertEquals('ModelCollection', $name);
    }
}
