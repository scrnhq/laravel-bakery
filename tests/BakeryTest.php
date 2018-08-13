<?php

namespace Bakery\Tests;

use Bakery\Support\Facades\Bakery;
use Bakery\Exceptions\TypeNotFound;
use Bakery\Types\Definitions\ObjectType;

class BakeryTest extends FeatureTestCase
{
    /** @test */
    public function it_throws_exception_for_unregistered_type()
    {
        $this->expectException(TypeNotFound::class);

        Bakery::resolve('WrongType');
    }
}
