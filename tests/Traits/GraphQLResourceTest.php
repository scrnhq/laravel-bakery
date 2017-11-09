<?php

namespace Scrn\Bakery\Tests\Traits;

use Scrn\Bakery\Tests\Stubs\Model;
use Scrn\Bakery\Tests\TestCase;

class GraphQLResourceTest extends TestCase
{
    /** @test */
    public function it_has_fields()
    {
        $fields = app(Model::class)->fields();

        $this->assertInternalType('array', $fields);
    }
}
